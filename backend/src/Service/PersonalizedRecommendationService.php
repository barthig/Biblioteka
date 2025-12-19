<?php
namespace App\Service;

use App\Entity\Book;
use App\Entity\Favorite;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\FavoriteRepository;
use App\Repository\LoanRepository;
use App\Repository\RatingRepository;
use App\Repository\RecommendationFeedbackRepository;
use App\Repository\CollectionRepository;

class PersonalizedRecommendationService
{
    private const MEMBERSHIP_AGE_GROUP_HINTS = [
        User::GROUP_CHILD => Book::AGE_GROUP_EARLY_SCHOOL,
        User::GROUP_STUDENT => Book::AGE_GROUP_YA_EARLY,
        User::GROUP_RESEARCHER => Book::AGE_GROUP_YA_LATE,
    ];

    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly FavoriteRepository $favoriteRepository,
        private readonly LoanRepository $loanRepository,
        private readonly RatingRepository $ratingRepository,
        private readonly RecommendationFeedbackRepository $feedbackRepository,
        private readonly CollectionRepository $collectionRepository,
    ) {
    }

    /**
     * Build grouped recommendations using the reader profile when available.
     *
     * @return array<int, array{key: string, label: string, description?: string|null, books: Book[]}> 
     */
    public function getRecommendationsForUser(?User $user, int $limitPerGroup = 8): array
    {
        
        // Get dismissed book IDs to exclude
        $dismissedBookIds = $user ? $this->feedbackRepository->getDismissedBookIdsByUser($user) : [];
        
        $favoriteBooks = $user ? $this->extractBooksFromFavorites($this->favoriteRepository->findByUser($user)) : [];
        
        $recentBooks = $user ? $this->extractBooksFromLoans($this->loanRepository->findRecentByUser($user, 12)) : [];
        
        // Get highly rated books for better recommendations
        $highlyRatedBookIds = $user ? $this->ratingRepository->findHighlyRatedBooksByUser($user, 10) : [];
        
        $seenBookIds = array_merge(
            $this->collectBookIds([...$favoriteBooks, ...$recentBooks]),
            $dismissedBookIds
        );

        $groups = [];
        
        // Featured collections (librarian-curated)
        $featuredCollections = $this->collectionRepository->findFeatured();
        foreach ($featuredCollections as $collection) {
            $collectionBooks = array_filter(
                $collection->getBooks()->toArray(),
                fn($book) => !in_array($book->getId(), $seenBookIds)
            );
            
            if (!empty($collectionBooks)) {
                $groups[] = [
                    'key' => 'collection-' . $collection->getId(),
                    'label' => $collection->getName(),
                    'description' => $collection->getDescription(),
                    'books' => array_slice($collectionBooks, 0, $limitPerGroup),
                ];
                $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($collectionBooks)];
            }
        }
        
        // New arrivals based on preferred categories
        if ($user && $user->getPreferredCategories()) {
            $newArrivals = $this->bookRepository->findNewArrivals(
                new \DateTimeImmutable('-3 months'),
                $limitPerGroup * 2
            );
            
            $filteredNewArrivals = array_filter(
                $newArrivals,
                function($book) use ($user, $seenBookIds) {
                    if (in_array($book->getId(), $seenBookIds)) {
                        return false;
                    }
                    
                    $bookCategories = array_map(fn($cat) => $cat->getName(), $book->getCategories()->toArray());
                    $preferredCategories = $user->getPreferredCategories();
                    
                    return !empty(array_intersect($bookCategories, $preferredCategories));
                }
            );
            
            if (!empty($filteredNewArrivals)) {
                $groups[] = [
                    'key' => 'new-in-your-categories',
                    'label' => 'Nowości w Twoich ulubionych kategoriach',
                    'description' => 'Ostatnio dodane książki z kategorii, które Cię interesują.',
                    'books' => array_slice($filteredNewArrivals, 0, $limitPerGroup),
                ];
                $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($filteredNewArrivals)];
            }
        }

        $ageGroup = $this->resolveAgeGroupPreference(array_merge($favoriteBooks, $recentBooks), $user);

        if ($ageGroup !== null) {
            $ageDefinitions = Book::getAgeGroupDefinitions();
            $groups[] = [
                'key' => 'age-group',
                'label' => sprintf('Dla wieku %s', $ageDefinitions[$ageGroup]['label'] ?? $ageGroup),
                'description' => 'Pozycje dopasowane do wieku lub grupy docelowej najczęściej wybieranych tytułów.',
                'books' => $this->bookRepository->findRecommendedByAgeGroup($ageGroup, $limitPerGroup, $seenBookIds),
            ];

            $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($groups[array_key_last($groups)]['books'])];
        }

        $favoritePreferences = $this->summarizePreferences($favoriteBooks);
        if ($favoritePreferences['authors'] || $favoritePreferences['categories']) {
            $groups[] = [
                'key' => 'favorites-similar',
                'label' => 'Podobne do ulubionych',
                'description' => 'Autorzy i kategorie inspirowane Twoimi ulubionymi tytułami.',
                'books' => $this->bookRepository->findRecommendedByPreferences(
                    $favoritePreferences['authors'],
                    $favoritePreferences['categories'],
                    $ageGroup,
                    $seenBookIds,
                    $limitPerGroup
                ),
            ];

            $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($groups[array_key_last($groups)]['books'])];
        }

        $recentPreferences = $this->summarizePreferences($recentBooks);
        if ($recentPreferences['authors'] || $recentPreferences['categories']) {
            $groups[] = [
                'key' => 'recently-read',
                'label' => 'W duchu ostatnich lektur',
                'description' => 'Tytuły podobne do książek, które ostatnio przeglądałeś lub wypożyczałeś.',
                'books' => $this->bookRepository->findRecommendedByPreferences(
                    $recentPreferences['authors'],
                    $recentPreferences['categories'],
                    $ageGroup,
                    $seenBookIds,
                    $limitPerGroup
                ),
            ];

            $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($groups[array_key_last($groups)]['books'])];
        }

        // Always add most borrowed books as a recommendation group
        $mostBorrowed = $this->bookRepository->findMostBorrowedBooks($limitPerGroup, $seenBookIds);
        if (!empty($mostBorrowed)) {
            $groups[] = [
                'key' => 'most-borrowed',
                'label' => 'Najczęściej wypożyczane',
                'description' => 'Top 10 tytułów najchętniej czytanych przez wszystkich czytelników.',
                'books' => $mostBorrowed,
            ];
            $seenBookIds = [...$seenBookIds, ...$this->collectBookIds($mostBorrowed)];
        }

        if (empty($groups)) {
            foreach (Book::getAgeGroupDefinitions() as $group => $definition) {
                $groups[] = [
                    'key' => 'age-' . $group,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'books' => $this->bookRepository->findRecommendedByAgeGroup($group, $limitPerGroup),
                ];
            }
        }

        return $groups;
    }

    /**
     * @param Favorite[] $favorites
     * @return Book[]
     */
    private function extractBooksFromFavorites(array $favorites): array
    {
        $books = [];
        foreach ($favorites as $favorite) {
            $book = $favorite->getBook();
            if ($book->getId() !== null) {
                $books[$book->getId()] = $book;
            }
        }

        return array_values($books);
    }

    /**
     * @param Loan[] $loans
     * @return Book[]
     */
    private function extractBooksFromLoans(array $loans): array
    {
        $books = [];
        foreach ($loans as $loan) {
            $book = $loan->getBook();
            if ($book->getId() !== null) {
                $books[$book->getId()] = $book;
            }
        }

        return array_values($books);
    }

    /**
     * @param Book[] $books
     * @return array{authors: int[], categories: int[]}
     */
    private function summarizePreferences(array $books): array
    {
        $authorScores = [];
        $categoryScores = [];

        foreach ($books as $book) {
            $author = $book->getAuthor();
            if ($author->getId() !== null) {
                $authorScores[$author->getId()] = ($authorScores[$author->getId()] ?? 0) + 1;
            }

            $categories = $book->getCategories();
            foreach ($categories as $category) {
                if ($category->getId() !== null) {
                    $categoryScores[$category->getId()] = ($categoryScores[$category->getId()] ?? 0) + 1;
                }
            }
        }

        arsort($authorScores);
        arsort($categoryScores);

        return [
            'authors' => array_slice(array_keys($authorScores), 0, 5),
            'categories' => array_slice(array_keys($categoryScores), 0, 5),
        ];
    }

    /**
     * @param Book[] $books
     * @return int[]
     */
    private function collectBookIds(array $books): array
    {
        $ids = [];
        foreach ($books as $book) {
            if ($book->getId() !== null) {
                $ids[$book->getId()] = $book->getId();
            }
        }

        return array_values($ids);
    }

    /**
     * @param Book[] $books
     */
    private function resolveAgeGroupPreference(array $books, ?User $user): ?string
    {
        $ageScores = [];
        foreach ($books as $book) {
            $ageGroup = $book->getTargetAgeGroup();
            if ($ageGroup !== null) {
                $ageScores[$ageGroup] = ($ageScores[$ageGroup] ?? 0) + 1;
            }
        }

        if (!empty($ageScores)) {
            arsort($ageScores);
            return array_key_first($ageScores);
        }

        if ($user) {
            $hint = self::MEMBERSHIP_AGE_GROUP_HINTS[$user->getMembershipGroup()] ?? null;
            if ($hint !== null && Book::isValidAgeGroup($hint)) {
                return $hint;
            }
        }

        return null;
    }
}
