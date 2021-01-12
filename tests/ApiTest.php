<?php

/*
 * This file is part of the ESQL project.
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
// api/tests/BooksTest.php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Book;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

class ApiTest extends ApiTestCase
{
    // This trait provided by HautelookAliceBundle will take care of refreshing the database content to a known state before each test
    use RefreshDatabaseTrait;

    public function testGetCollection(): void
    {
        // The client implements Symfony HttpClient's `HttpClientInterface`, and the response `ResponseInterface`
        $response = static::createClient()->request('GET', '/cars');

        dump($response->getContent(false));
        // $this->assertResponseIsSuccessful();
        // // Asserts that the returned content type is JSON-LD (the default)
        // $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        //
        // // Asserts that the returned JSON is a superset of this one
        // $this->assertJsonContains([
        //     '@context' => '/contexts/Book',
        //     '@id' => '/books',
        //     '@type' => 'hydra:Collection',
        //     'hydra:totalItems' => 100,
        //     'hydra:view' => [
        //         '@id' => '/books?page=1',
        //         '@type' => 'hydra:PartialCollectionView',
        //         'hydra:first' => '/books?page=1',
        //         'hydra:last' => '/books?page=4',
        //         'hydra:next' => '/books?page=2',
        //     ],
        // ]);
        //
        // // Because test fixtures are automatically loaded between each test, you can assert on them
        // $this->assertCount(30, $response->toArray()['hydra:member']);
        //
        // // Asserts that the returned JSON is validated by the JSON Schema generated for this resource by API Platform
        // // This generated JSON Schema is also used in the OpenAPI spec!
        // $this->assertMatchesResourceCollectionJsonSchema(Book::class);
    }
}
