<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var Mailchimp
     */
    private $mailChimp;

    /**
     * MembersController constructor.
     * @param EntityManagerInterface $entityManager
     * @param Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Gets list of all members.
     *
     * @param string $subscriber
     * @return JsonResponse
     */
    public function all(string $subscriber): JsonResponse
    {
        $members = null;
        $list = $this->getListById($subscriber);

        // serves as a guard clause
        if ($list === null) {
            return $this->errorList($subscriber);
        } else {
            $members = $this->getMembersByList($subscriber);
        }

        // serves as a guard clause
        if ($members == null) {
            return $this->errorMembers($subscriber);
        }

        return $this->successfulResponse($members);
    }

    /**
     * Gets a member in a list by given parameters; subscriber id and member id.
     *
     * @param string $subscriber
     * @param string $member
     * @return JsonResponse
     */
    public function show(string $subscriber, string $member): JsonResponse
    {
        $members = null;
        $list = $this->getListById($subscriber);

        // serves as a guard clause
        if ($list === null) {
            return $this->errorList($subscriber);
        } else {
            $members = $this->getMemberByList($member, $subscriber);
        }

        // serves as a guard clause
        if ($members === null || empty($members)) {
            return $this->errorMember($member, $subscriber);
        }

        // Making sure we address non-shifted indexes
        return $this->successfulResponse($members[array_key_first($members)]->toArray());
    }

    /**
     * @param Request $request
     * @param string $subscriber
     * @return JsonResponse
     */
    public function create(Request $request, string $subscriber): JsonResponse
    {
        $mailchimpId = null;
        $list = $this->getListById($subscriber);

        // serves as a guard clause
        if ($list === null) {
            return $this->errorList($subscriber);
        } else {
            $mailchimpId = $list->getMailChimpId();
            if (is_string($mailchimpId) && strlen($mailchimpId) === 0) {
                return $this->errorInvalidMailChimpId($subscriber);
            }
        }

        // Handle truthy boolean value for `vip` payload field
        if ($request->filled('vip')) {
            $vip = filter_var($request->get('vip'), FILTER_VALIDATE_BOOLEAN);
            $request->merge(['vip' => $vip]);
        }

        // Making sure `list_id` field is attached to payload
        if (!$request->filled('list_id')) {
            $request->request->add(['list_id' => $subscriber]);
        }

        // Instantiate entity
        $member = new MailChimpMember($request->all());
        $memberData = $member->toMailChimpArray();

        // Validate entity
        $validator = $this->getValidationFactory()->make($memberData, $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorInvalidPayload($validator->errors()->toArray());
        }

        // Validate whether email in payload already exists in list
        $email = $request->get('email_address');

        $emailSubscribed = $this->verifyEmailSubscribed($subscriber, $email);

        if ($emailSubscribed) {
            return $this->errorEmailSubscribed($subscriber);
        }

        try {
            // Save new member to Mailchimp list
            $response = $this->mailChimp->post("lists/{$mailchimpId}/members", $memberData);

            // Set MailChimp id on the list and save list into db
            $this->saveEntity($member->setMailChimpId($response->get('id')));

        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * @param string $subscriber
     * @param string $member
     * @return JsonResponse
     */
    public function remove(string $subscriber, string $member): JsonResponse
    {
        $mailchimpId = null;
        $list = $this->getListbyId($subscriber);
        // serves as a guard clause
        if ($list === null) {
            return $this->errorList($subscriber);
        } else {
            $memberData = $this->getMemberByList($member, $subscriber);
            $mailchimpId = $list->getMailChimpId();

            if (is_string($mailchimpId) && strlen($mailchimpId) === 0) {
                return $this->errorInvalidMailChimpId($subscriber);
            }
        }

        // serves as a guard clause
        if (empty($memberData)) {
            return $this->errorMember($member, $subscriber);
        }

        try {
            // Making sure we address non-shifted indexes
            $memberBody = $memberData[array_key_first($memberData)];
            // Remove member from Mailchimp list
            $this->mailChimp->delete(\sprintf('lists/%s/members/%s', $mailchimpId, $memberBody->getMailChimpId()));

            // Remove our local DB member entry
            $this->removeEntity($memberBody);

        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    public function update(Request $request, string $subscriber, string $member): JsonResponse
    {
        $mailchimpId = null;
        $list = $this->getListbyId($subscriber);

        // serves as a guard clause
        if ($list === null) {
            return $this->errorList($subscriber);
        } else {
            $memberData = $this->getMemberByList($member, $subscriber);
            $listMailchimpId = $list->getMailChimpId();

            if (is_string($mailchimpId) && strlen($mailchimpId) === 0) {
                return $this->errorInvalidMailChimpId($subscriber);
            }
        }

        // serves as a guard clause
        if (empty($memberData)) {
            return $this->errorMember($member, $subscriber);
        }

        // Making sure we address non-shifted indexes
        $memberBody = $memberData[array_key_first($memberData)];

        // Update member properties
        $memberBody->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($memberBody->toMailChimpArray(),
            $memberBody->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Update member into MailChimp server ( PUT )
            $response = $this->mailChimp->patch('lists/' . $listMailchimpId . '/members/' . $memberBody->getMailChimpId(),
                $memberBody->toMailChimpArray());

            //Check for member ID on Mailchimp server, if changed, update the DB
            if ($response->get('id') != $memberBody->getMailChimpId()) {
                $memberBody->setMailChimpId($response->get('id'));
            }

            // Update member info into DB
            $this->saveEntity($memberBody);

        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($memberBody->toArray());
    }


    /**
     * Find member by given list id and member id.
     *
     * @param string $member
     * @param string $list
     * @return array|null
     */
    private function getMemberByList(string $member, string $list): ?array
    {
        return $this->entityManager->getRepository(MailChimpMember::class)->findBy([
            'memberId' => $member,
            'listId' => $list
        ]);
    }

    /**
     * Find member(s) by given list id.
     *
     * @param string $list
     * @return array|null
     */
    private function getMembersByList(string $list): ?array
    {
        return $this->entityManager->getRepository(MailChimpMember::class)->findBy(['listId' => $list]);
    }

    /**
     * Find members with given list id and email address.
     *
     * @param string $list
     * @param $email
     * @return object[]
     */
    private function getListMemberByEmail(string $list, $email): ?array
    {
        return $this->entityManager->getRepository(MailChimpMember::class)->findBy([
            'listId' => $list,
            'emailAddress' => $email
        ]);
    }

    /**
     * Validates whether an email exists in a list.
     *
     * @param string $list
     * @param string $email
     * @return bool
     */
    private function verifyEmailSubscribed(string $list, string $email): bool
    {
        return count($this->getListMemberByEmail($list, $email)) > 0;
    }
}