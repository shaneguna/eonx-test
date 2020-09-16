<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{

    /**
     * Test application creates successfully list and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists/' . $this->listId . '/members', static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        $this->seeJson(static::$memberData);
        self::assertArrayHasKey('mail_chimp_id', $content);
        self::assertNotNull($content['mail_chimp_id']);
    }

    /**
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $this->post('/mailchimp/lists/' . $this->listId . '/members');

        $content = json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

        foreach (array_keys(static::$memberData) as $key) {
            if (in_array($key, static::$notRequired, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

    /**
     * @return void
     */
    public function testCreateMemberFromInvalidList(): void
    {
        $this->post('/mailchimp/lists/invalid-list-id/members', self::$memberData);

        $this->assertListNotFoundResponse('invalid-list-id');
    }

    /**
     * @return void
     */
    public function testCreateMemberWithDuplicateEmail(): void
    {
        self::$duplicateMailData['email_address'] = $this->createEmail();
        // Create test for member with duplicate email
        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$duplicateMailData);
        $this->assertResponseStatus(200);

        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$duplicateMailData);
        $content = json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('Email exists in given list:' . $this->listId, $content['message']);
    }

    /**
     * Test application returns successful response when updating existing member with new values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $mockEmail = $this->createEmail();
        $updateBody = ['email_address' => $mockEmail];

        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$memberData);
        $member = json_decode($this->response->content(), true);

        $this->assertArrayHasKey('member_id', $member);

        $this->patch('/mailchimp/lists/' . $this->listId . '/members/' . $member['member_id'], $updateBody);

        $content = json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (array_keys($updateBody) as $key) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($mockEmail, $content[$key]);
        }
    }

    /**
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
        $this->post('/mailchimp/lists/' . $this->listId . '/members', static::$memberData);
        $member = json_decode($this->response->getContent(), true);
        $this->assertResponseStatus(200);

        $this->patch('/mailchimp/lists/' . $this->listId . '/members/' . $member['member_id'],
            ['email_address' => 'invalid-value']);

        $content = json_decode($this->response->getContent(), true);
        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

        foreach (array_keys(static::$memberData) as $key) {
            if (in_array($key, static::$notRequired, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

    /**
     * Test application returns error response when attempting to update a member in a non-existing list.
     *
     * @return void
     */
    public function testUpdateMemberFromInvalidList(): void
    {
        $this->patch('/mailchimp/lists/invalid-list-id/members/invalid-member-id');

        $this->assertListNotFoundResponse('invalid-list-id');
    }

    /**
     * Test application returns error response when member to update is not found in a valid list.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        $this->patch('/mailchimp/lists/' . $this->listId . '/members/invalid-member-id');

        $this->assertMemberNotFoundResponse('invalid-member-id', $this->listId);
    }

    /**
     * Test application returns empty successful response when removing member in an existing list.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        $this->post('/mailchimp/lists/' . $this->listId . '/members', static::$memberData);

        $content = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('member_id', $content);

        $this->delete('/mailchimp/lists/' . $this->listId . '/members/' . $content['member_id']);

        $this->assertResponseOk();
        self::assertEmpty(json_decode($this->response->content(), true));
    }

    /**
     * Test application returns error response when removing member on a non-existent list.
     *
     * @return void
     */
    public function testRemoveMemberFromInvalidList(): void
    {
        $this->delete('/mailchimp/lists/invalid_list_id/members/invalid_member_id');

        $this->assertListNotFoundResponse('invalid_list_id');
    }

    /**
     * Test application returns error response when member not found in an existing list.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        $this->delete('/mailchimp/lists/' . $this->listId . '/members/invalid_member_id');

        $this->assertMemberNotFoundResponse('invalid_member_id', $this->listId);
    }

    /**
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        $this->get('/mailchimp/lists/' . $this->listId . '/members/invalid_member_id');

        $this->assertMemberNotFoundResponse('invalid_member_id', $this->listId);
    }

    /**
     *
     * @return void
     */
    public function testShowAllMembersSuccessfully(): void
    {
        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$memberData);

        $content = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('member_id', $content);

        $this->get('/mailchimp/lists/' . $this->listId . '/members/' . $content['member_id']);

        $content = json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (self::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($value, $content[$key]);
        }
    }
}
