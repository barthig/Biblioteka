BEGIN;
DELETE FROM book_category bc USING book b WHERE bc.book_id = b.id AND b.isbn IN ('978-83-7469-627-4', '978-83-08-06384-6', '978-83-7506-127-3', '978-83-240-0942-7', '978-83-08-03371-9', '978-83-240-1234-5', '978-83-08-04567-8', '978-83-7327-823-4', '978-83-240-5678-9', '978-83-7469-234-5', '978-83-1409-859-4', '978-83-2679-792-8', '978-83-1488-195-3', '978-83-1434-674-3', '978-83-8359-703-4', '978-83-6574-384-2', '978-83-2519-489-1', '978-83-5333-926-0', '978-83-7201-180-8', '978-83-6925-691-3', '978-83-5741-181-3', '978-83-8428-750-5', '978-83-4432-786-4', '978-83-5010-267-7', '978-83-4598-801-5', '978-83-6168-510-4', '978-83-6155-317-7', '978-83-5339-242-3', '978-83-8019-697-6', '978-83-9348-605-1');
DELETE FROM book_copy bc USING book b WHERE bc.book_id = b.id AND b.isbn IN ('978-83-7469-627-4', '978-83-08-06384-6', '978-83-7506-127-3', '978-83-240-0942-7', '978-83-08-03371-9', '978-83-240-1234-5', '978-83-08-04567-8', '978-83-7327-823-4', '978-83-240-5678-9', '978-83-7469-234-5', '978-83-1409-859-4', '978-83-2679-792-8', '978-83-1488-195-3', '978-83-1434-674-3', '978-83-8359-703-4', '978-83-6574-384-2', '978-83-2519-489-1', '978-83-5333-926-0', '978-83-7201-180-8', '978-83-6925-691-3', '978-83-5741-181-3', '978-83-8428-750-5', '978-83-4432-786-4', '978-83-5010-267-7', '978-83-4598-801-5', '978-83-6168-510-4', '978-83-6155-317-7', '978-83-5339-242-3', '978-83-8019-697-6', '978-83-9348-605-1');
DELETE FROM book b WHERE b.isbn IN ('978-83-7469-627-4', '978-83-08-06384-6', '978-83-7506-127-3', '978-83-240-0942-7', '978-83-08-03371-9', '978-83-240-1234-5', '978-83-08-04567-8', '978-83-7327-823-4', '978-83-240-5678-9', '978-83-7469-234-5', '978-83-1409-859-4', '978-83-2679-792-8', '978-83-1488-195-3', '978-83-1434-674-3', '978-83-8359-703-4', '978-83-6574-384-2', '978-83-2519-489-1', '978-83-5333-926-0', '978-83-7201-180-8', '978-83-6925-691-3', '978-83-5741-181-3', '978-83-8428-750-5', '978-83-4432-786-4', '978-83-5010-267-7', '978-83-4598-801-5', '978-83-6168-510-4', '978-83-6155-317-7', '978-83-5339-242-3', '978-83-8019-697-6', '978-83-9348-605-1');
WITH new_authors(name) AS (
    VALUES
    ('Agatha Christie'),
    ('Andre Malraux'),
    ('Andrzej Sapkowski'),
    ('Czeslaw Milosz'),
    ('Dan Brown'),
    ('Fiodor Dostojewski'),
    ('Frank Herbert'),
    ('Gabriel Garcia Marquez'),
    ('George Orwell'),
    ('George R. R. Martin'),
    ('Georges Duby'),
    ('Gustave Le Bon'),
    ('Haruki Murakami'),
    ('Henryk Sienkiewicz'),
    ('Isaac Asimov'),
    ('J. K. Rowling'),
    ('J. R. R. Tolkien'),
    ('Jane Austen'),
    ('Margaret Atwood'),
    ('Michail Bulgakov'),
    ('Neil Gaiman'),
    ('Olga Tokarczuk'),
    ('Philip K. Dick'),
    ('Ryszard Kapuscinski'),
    ('Stanislaw Lem'),
    ('Stephen King'),
    ('Umberto Eco'),
    ('Wislawa Szymborska'),
    ('Witold Gombrowicz'),
    ('Yuval Noah Harari')
),
ins_authors AS (
    INSERT INTO author (name)
    SELECT name FROM new_authors
    ON CONFLICT (name) DO NOTHING
    RETURNING id, name
),
author_map AS (
    SELECT a.id, a.name FROM author a JOIN new_authors na ON a.name = na.name
),
new_categories(name) AS (
    VALUES
    ('Classic'),
    ('Crime'),
    ('Dystopia'),
    ('Essay'),
    ('Fantasy'),
    ('Historical Fiction'),
    ('History'),
    ('Horror'),
    ('Literary'),
    ('Magical Realism'),
    ('Nonfiction'),
    ('Philosophy'),
    ('Poetry'),
    ('Psychology'),
    ('Reportage'),
    ('Romance'),
    ('Science Fiction'),
    ('Thriller'),
    ('Young Adult')
),
ins_categories AS (
    INSERT INTO category (name)
    SELECT name FROM new_categories
    ON CONFLICT (name) DO NOTHING
    RETURNING id, name
),
category_map AS (
    SELECT c.id, c.name FROM category c JOIN new_categories nc ON c.name = nc.name
),
book_rows AS (
    VALUES
    ('Wiedzmin: Ostatnie zyczenie', '978-83-7469-627-4', 'Andrzej Sapkowski', 5, 5, 3, 2, 'Zbior opowiadan wprowadzajacy postac Geralta z Rivii.', 'SuperNOWA', 1993, 'Ksiazka drukowana', 'F-SAP-001', 'adult'),
    ('Ksiega Jakubowe', '978-83-08-06384-6', 'Olga Tokarczuk', 3, 3, 2, 1, 'Epicka opowiesc o Jakubie Franku i jego wspolnocie.', 'Wydawnictwo Literackie', 2014, 'Ksiazka drukowana', 'L-TOK-001', 'adult'),
    ('Solaris', '978-83-7506-127-3', 'Stanislaw Lem', 4, 4, 2, 2, 'Klasyczna powiesc SF o niepoznawalnej inteligencji planety.', 'Wydawnictwo Literackie', 1961, 'Ksiazka drukowana', 'SF-LEM-001', 'adult'),
    ('Imperium', '978-83-240-0942-7', 'Ryszard Kapuscinski', 2, 2, 1, 1, 'Reportaz o rozpadzie ZSRR i podrozy po jego peryferiach.', 'Czytelnik', 1993, 'Ksiazka drukowana', 'R-KAP-001', 'adult'),
    ('Sto pociech', '978-83-08-03371-9', 'Wislawa Szymborska', 2, 2, 2, 0, 'Zbior wierszy noblistki z lat 60. XX wieku.', 'Wydawnictwo a5', 1967, 'Ksiazka drukowana', 'P-SZY-001', 'adult'),
    ('Quo Vadis', '978-83-240-1234-5', 'Henryk Sienkiewicz', 6, 6, 4, 2, 'Powiesc historyczna z czasow Nerona i pierwszych chrzescijan.', 'Czytelnik', 1896, 'Ksiazka drukowana', 'H-SIE-001', 'teen'),
    ('Zniewolony umysl', '978-83-08-04567-8', 'Czeslaw Milosz', 3, 3, 2, 1, 'Esej o postawach intelektualistow wobec totalitaryzmu.', 'Instytut Literacki', 1953, 'Ksiazka drukowana', 'E-MIL-001', 'adult'),
    ('Harry Potter i Kamien Filozoficzny', '978-83-7327-823-4', 'J. K. Rowling', 8, 8, 4, 4, 'Poczatek przygod mlodego czarodzieja w Hogwarcie.', 'Media Rodzina', 1997, 'E-book', 'F-ROW-001', 'child'),
    ('Rok 1984', '978-83-240-5678-9', 'George Orwell', 5, 5, 3, 2, 'Dystopia o spoleczenstwie pod wszechobecna kontrola.', 'Muza', 1949, 'Ksiazka drukowana', 'SF-ORW-001', 'adult'),
    ('Fundacja', '978-83-7469-234-5', 'Isaac Asimov', 4, 4, 2, 2, 'Pierwszy tom cyklu o psychohistorii i upadku imperium.', 'Zysk i S-ka', 1951, 'Ksiazka drukowana', 'SF-ASI-001', 'teen'),
    ('Mistrz i Malgorzata', '978-83-1409-859-4', 'Michail Bulgakov', 5, 5, 4, 1, 'Satyryczna powiesc z watkiem diabelskiej wizyty w Moskwie.', 'Czytelnik', 1967, 'Ksiazka drukowana', 'SIG-11-011', 'adult'),
    ('Zbrodnia i kara', '978-83-2679-792-8', 'Fiodor Dostojewski', 3, 3, 0, 3, 'Psychologiczna powiesc o winie, karze i odkupieniu.', 'Wydawnictwo Literackie', 1866, 'Ksiazka drukowana', 'SIG-12-012', 'adult'),
    ('Duma i uprzedzenie', '978-83-1488-195-3', 'Jane Austen', 5, 5, 1, 4, 'Klasyka literatury obyczajowej o rodzinie Bennetow.', 'Penguin Classics', 1813, 'E-book', 'SIG-13-013', 'teen'),
    ('Gra o tron', '978-83-1434-674-3', 'George R. R. Martin', 10, 10, 4, 6, 'Pierwszy tom sagi o walce o wladze w Westeros.', 'Zysk i S-ka', 1996, 'Audiobook', 'SIG-14-014', 'adult'),
    ('Diuna', '978-83-8359-703-4', 'Frank Herbert', 2, 2, 2, 0, 'Epicka powiesc SF o planecie Arrakis i walce rodow.', 'Rebis', 1965, 'Ksiazka drukowana', 'SIG-15-015', 'teen'),
    ('American Gods', '978-83-6574-384-2', 'Neil Gaiman', 5, 5, 3, 2, 'Opowiesc o starych i nowych bogach we wspolczesnej Ameryce.', 'Mag', 2001, 'E-book', 'SIG-16-016', 'adult'),
    ('Opowiesc podrecznej', '978-83-2519-489-1', 'Margaret Atwood', 7, 7, 2, 5, 'Antyutopia o spoleczenstwie Gilead i kontroli nad kobietami.', 'Zysk i S-ka', 1985, 'Ksiazka drukowana', 'SIG-17-017', 'adult'),
    ('Lsnienie', '978-83-5333-926-0', 'Stephen King', 9, 9, 1, 8, 'Thriller o hotelu Overlook i narastajacym obledzie.', 'Albatros', 1977, 'Ksiazka drukowana', 'SIG-18-018', 'adult'),
    ('Morderstwo w Orient Expressie', '978-83-7201-180-8', 'Agatha Christie', 6, 6, 0, 6, 'Kryminal z Herkulesem Poirot i zagadka w pociagu.', 'Wydawnictwo Dolnoslaskie', 1934, 'E-book', 'SIG-19-019', 'teen'),
    ('Norwegian Wood', '978-83-6925-691-3', 'Haruki Murakami', 3, 3, 3, 0, 'Nostalgiczna opowiesc o dorastaniu w Tokio lat 60.', 'Muza', 1987, 'Audiobook', 'SIG-20-020', 'adult'),
    ('Sto lat samotnosci', '978-83-5741-181-3', 'Gabriel Garcia Marquez', 3, 3, 0, 3, 'Saga rodu Buendia i magiczny realizm Macondo.', 'Wydawnictwo Literackie', 1967, 'Ksiazka drukowana', 'SIG-21-021', 'adult'),
    ('Imie rozy', '978-83-8428-750-5', 'Umberto Eco', 4, 4, 2, 2, 'Intryga kryminalna w sredniowiecznym klasztorze.', 'Noir Sur Blanc', 1980, 'E-book', 'SIG-22-022', 'adult'),
    ('Kod Leonarda da Vinci', '978-83-4432-786-4', 'Dan Brown', 3, 3, 2, 1, 'Thriller o tajemnicy zakonnej i szyfrach sztuki.', 'Sonia Draga', 2003, 'Audiobook', 'SIG-23-023', 'teen'),
    ('Czlowiek z Wysokiego Zamku', '978-83-5010-267-7', 'Philip K. Dick', 8, 8, 4, 4, 'Alternatywna historia swiata po zwyciestwie osi.', 'Rebis', 1962, 'Ksiazka drukowana', 'SIG-24-024', 'adult'),
    ('Wladca Pierscieni: Druzyna Pierscienia', '978-83-4598-801-5', 'J. R. R. Tolkien', 2, 2, 2, 0, 'Pierwszy tom trylogii o Wladcy Pierscieni.', 'Muza', 1954, 'E-book', 'SIG-25-025', 'teen'),
    ('Kosmos', '978-83-6168-510-4', 'Witold Gombrowicz', 3, 3, 2, 1, 'Groteskowa powiesc o poszukiwaniu sensu i znakow.', 'Wydawnictwo Literackie', 1965, 'Ksiazka drukowana', 'SIG-26-026', 'adult'),
    ('Sapiens: Od zwierzat do bogow', '978-83-6155-317-7', 'Yuval Noah Harari', 8, 8, 1, 7, 'Historia czlowieka od pradziejow po wspolczesnosc.', 'PWN', 2011, 'Ksiazka drukowana', 'SIG-27-027', 'adult'),
    ('Kondycja ludzka', '978-83-5339-242-3', 'Andre Malraux', 10, 10, 2, 8, 'Powiesc o rewolucji w Szanghaju i dylematach moralnych.', 'Gallimard', 1933, 'E-book', 'SIG-28-028', 'adult'),
    ('Rok 1000', '978-83-8019-697-6', 'Georges Duby', 7, 7, 4, 3, 'Popularnonaukowy portret Europy u progu drugiego milenium.', 'PWN', 1989, 'Audiobook', 'SIG-29-029', 'adult'),
    ('Psychologia tlumu', '978-83-9348-605-1', 'Gustave Le Bon', 2, 2, 2, 0, 'Klasyczne studium zachowan zbiorowych i mechanizmow mas.', 'PWN', 1895, 'Ksiazka drukowana', 'SIG-30-030', 'adult')
),
inserted_books AS (
    INSERT INTO book (title, isbn, author_id, copies, total_copies, storage_copies, open_stack_copies, description, publisher, publication_year, resource_type, signature, target_age_group, created_at)
    SELECT br.column1, br.column2, am.id, br.column4, br.column5, br.column6, br.column7, br.column8, br.column9, br.column10, br.column11, br.column12, br.column13, NOW()
    FROM book_rows br
    JOIN author_map am ON am.name = br.column3
    RETURNING id, isbn, total_copies, open_stack_copies
),
book_category_rows AS (
    VALUES
    ('978-83-7469-627-4', 'Fantasy'),
    ('978-83-08-06384-6', 'Historical Fiction'),
    ('978-83-08-06384-6', 'Literary'),
    ('978-83-7506-127-3', 'Science Fiction'),
    ('978-83-240-0942-7', 'Reportage'),
    ('978-83-240-0942-7', 'History'),
    ('978-83-08-03371-9', 'Poetry'),
    ('978-83-240-1234-5', 'Historical Fiction'),
    ('978-83-240-1234-5', 'Classic'),
    ('978-83-08-04567-8', 'Essay'),
    ('978-83-08-04567-8', 'Philosophy'),
    ('978-83-7327-823-4', 'Fantasy'),
    ('978-83-7327-823-4', 'Young Adult'),
    ('978-83-240-5678-9', 'Dystopia'),
    ('978-83-240-5678-9', 'Science Fiction'),
    ('978-83-7469-234-5', 'Science Fiction'),
    ('978-83-1409-859-4', 'Classic'),
    ('978-83-1409-859-4', 'Literary'),
    ('978-83-2679-792-8', 'Classic'),
    ('978-83-2679-792-8', 'Psychology'),
    ('978-83-1488-195-3', 'Romance'),
    ('978-83-1488-195-3', 'Classic'),
    ('978-83-1434-674-3', 'Fantasy'),
    ('978-83-8359-703-4', 'Science Fiction'),
    ('978-83-6574-384-2', 'Fantasy'),
    ('978-83-2519-489-1', 'Dystopia'),
    ('978-83-2519-489-1', 'Literary'),
    ('978-83-5333-926-0', 'Horror'),
    ('978-83-5333-926-0', 'Thriller'),
    ('978-83-7201-180-8', 'Crime'),
    ('978-83-6925-691-3', 'Literary'),
    ('978-83-5741-181-3', 'Literary'),
    ('978-83-5741-181-3', 'Magical Realism'),
    ('978-83-8428-750-5', 'Crime'),
    ('978-83-8428-750-5', 'Historical Fiction'),
    ('978-83-4432-786-4', 'Thriller'),
    ('978-83-5010-267-7', 'Science Fiction'),
    ('978-83-4598-801-5', 'Fantasy'),
    ('978-83-6168-510-4', 'Literary'),
    ('978-83-6168-510-4', 'Philosophy'),
    ('978-83-6155-317-7', 'Nonfiction'),
    ('978-83-6155-317-7', 'History'),
    ('978-83-5339-242-3', 'Historical Fiction'),
    ('978-83-8019-697-6', 'History'),
    ('978-83-8019-697-6', 'Nonfiction'),
    ('978-83-9348-605-1', 'Psychology'),
    ('978-83-9348-605-1', 'Nonfiction')
),
book_category_insert AS (
    INSERT INTO book_category (book_id, category_id)
    SELECT ib.id, cm.id
    FROM book_category_rows bcr
    JOIN inserted_books ib ON ib.isbn = bcr.column1
    JOIN category_map cm ON cm.name = bcr.column2
    ON CONFLICT DO NOTHING
    RETURNING 1
),
book_copy_insert AS (
    INSERT INTO book_copy (inventory_code, status, location, access_type, condition_state, created_at, updated_at, book_id)
    SELECT
        'REAL-' || ib.id || '-' || lpad(gs::text, 2, '0'),
        'AVAILABLE',
        CASE WHEN gs <= ib.open_stack_copies THEN 'Open Stack' ELSE 'Storage' END,
        CASE WHEN gs <= ib.open_stack_copies THEN 'OPEN_STACK' ELSE 'STORAGE' END,
        'Good',
        NOW(), NOW(), ib.id
    FROM inserted_books ib
    JOIN generate_series(1, ib.total_copies) gs ON true
    RETURNING 1
)
SELECT
    (SELECT count(*) FROM inserted_books) AS inserted_books,
    (SELECT count(*) FROM book_category_insert) AS book_categories_added,
    (SELECT count(*) FROM book_copy_insert) AS book_copies_added;
COMMIT;
