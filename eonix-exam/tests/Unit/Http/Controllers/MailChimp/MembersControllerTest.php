<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse($controller->create($this->getRequest(self::$memberData),
            $this->listId));
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMemberMailChimpException(): void
    {
        self::$memberData['email_address'] = $this->createEmail();

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('delete'));

        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$memberData);

        $member = json_decode($this->response->content(), true);

        $this->createMember($member);

        // If there is no member_id, skip
        if (null === $member['member_id']) {
            self::markTestSkipped('Unable to remove, no id provided for member');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->remove($this->listId, $member['member_id']));
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('patch'));

        $this->post('/mailchimp/lists/' . $this->listId . '/members', self::$memberData);

        $member = json_decode($this->response->content(), true);

        $this->createMember($member);

        // If there is no list id, skip
        if (null === $member['member_id']) {
            self::markTestSkipped('Unable to update, no id provided for list');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->update($this->getRequest(), $this->listId,
            $member['member_id']));
    }
}
