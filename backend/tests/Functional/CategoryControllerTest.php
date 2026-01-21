<?php
namespace App\Tests\Functional;

use App\Entity\Category;

class CategoryControllerTest extends ApiTestCase
{
    public function testListCategoriesReturnsData(): void
    {
        $this->createCategory('Sci-Fi');
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/categories');

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetCategoryReturnsData(): void
    {
        $category = $this->createCategory('History');
        $client = $this->createApiClient();
        $this->sendRequest($client, 'GET', '/api/categories/' . $category->getId());

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateCategoryRequiresLibrarian(): void
    {
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'POST', '/api/categories', ['name' => 'New']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCategoryCreatesEntry(): void
    {
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'POST', '/api/categories', ['name' => 'New Category']);

        $this->assertResponseStatusCodeSame(201);
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => 'New Category']);
        $this->assertNotNull($category);
    }

    public function testUpdateCategoryRequiresLibrarian(): void
    {
        $category = $this->createCategory('Old Name');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->jsonRequest($client, 'PUT', '/api/categories/' . $category->getId(), ['name' => 'Updated']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateCategoryUpdatesEntry(): void
    {
        $category = $this->createCategory('Old Name');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->jsonRequest($client, 'PUT', '/api/categories/' . $category->getId(), ['name' => 'Updated']);

        $this->assertResponseStatusCodeSame(200);
        $updated = $this->entityManager->getRepository(Category::class)->find($category->getId());
        $this->assertSame('Updated', $updated->getName());
    }

    public function testDeleteCategoryRequiresLibrarian(): void
    {
        $category = $this->createCategory('To Delete');
        $client = $this->createAuthenticatedClient($this->createUser('reader@example.com'));
        $this->sendRequest($client, 'DELETE', '/api/categories/' . $category->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteCategoryRemovesEntry(): void
    {
        $category = $this->createCategory('To Delete');
        $librarian = $this->createUser('librarian@example.com', ['ROLE_LIBRARIAN']);
        $client = $this->createAuthenticatedClient($librarian);
        $this->sendRequest($client, 'DELETE', '/api/categories/' . $category->getId());

        $this->assertResponseStatusCodeSame(204);
    }
}
