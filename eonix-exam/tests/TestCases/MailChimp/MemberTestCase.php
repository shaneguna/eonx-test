<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * @var string
     */
    protected $listId;

    /**
     * @var string
     */
    protected $listMailchimpId;

    /**
     * @var array
     */
    protected $createdMemberIds = [];

    /**
     * @var array
     */
    protected static $memberData = [
        'email_address' => '',
        'email_id' => '',
        'unique_email_id' => '',
        'member_rating' => 5,
        'email_type' => null,
        'status' => 'subscribed',
        'language' => 'US English',
        'vip' => true,
        'location' => [
            'latitude' => 0,
            'longitude' => 0
        ],
        'ip_signup' => '10.20.10.30',
        'tags' => ['sports']
    ];

    protected static $duplicateMailData = [
        'email_address' => 'examdesu@gmail.com',
        'email_type' => null,
        'status' => 'subscribed',
        'language' => 'US English',
        'vip' => true,
        'location' => [
            'latitude' => 0,
            'longitude' => 0
        ],
        'ip_signup' => '10.20.10.30',
        'tags' => ['sports']
    ];

    /**
     * @var array
     */
    protected static $listData = [
        'name' => 'New list',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => true,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'unsubscribe_reason',
        'member_rating',
        'email_type',
        'email_id',
        'unique_email_id',
        'status',
        'vip',
        'language',
        'tags',
        'email_type',
        'merge_fields',
        'interests',
        'language',
        'location',
        'ip_signup',
        'timestamp_signup',
        'timestamp_opt',
        'tags'
    ];

    /**
     * Call MailChimp to delete lists created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        // Delete list after test, includes members
        $mailChimp->delete('lists/' . $this->listMailchimpId);

        parent::tearDown();
    }

    /**
     * Asserts error response when member not found in a valid list.
     *
     * @param string $memberId
     * @param string $listId
     */
    protected function assertMemberNotFoundResponse(string $memberId, string $listId): void
    {
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('Mailchimp member: ' . $memberId . ' not found for given list. ' . $listId,
            $content['message']);
    }

    /**
     * Asserts error response when list not found.
     *
     * @param string $listId
     */
    protected function assertListNotFoundResponse(string $listId): void
    {
        $content = json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('Mailchimp list not found. ' . $listId, $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param JsonResponse $response
     * @param int $errorCode
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

/*    protected function assertMailChimpDeleteExceptionResponse(JsonResponse $response, string $memberId): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('Mailchimp member: %s not found for given list. ', $memberId) . $this->listId, $content['message']);
    }*/

    /**
     * @param array $data
     * @return MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {
        $member = new MailChimpMember($data);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Create MailChimp list into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpList
     */
    protected function createList(array $data): MailChimpList
    {
        $list = new MailChimpList($data);

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }

    /**
     * Extend to Setup method with our requirements
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$memberData['email_address'] = $this->createEmail();

        // Create initial test list for the whole members testing suite
        $this->post('/mailchimp/lists', static::$listData);

        $content = json_decode($this->response->getContent(), true);
        $this->createList($content);

        $this->assertResponseOk();
        $this->seeJson(static::$listData);

        // Set mock id's to class properties
        $this->listId = $content['list_id'];
        $this->listMailchimpId = $content['mail_chimp_id'];
    }

    /**
     * Resolves spam issues with static e-mail addresses. Dynamically create emails for test.
     * Mailchimp error response: <email> has signed up to a lot of lists very recently; we're not allowing more signups for now.
     *
     * @return string
     */
    public function createEmail(): string
    {
        return 'test_' . substr(str_shuffle('0123456789'), 1, 10) . '@foobar.com';
    }
}
