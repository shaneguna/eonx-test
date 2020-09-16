<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Database\Entities\Entity;
use App\Database\Entities\MailChimp\MailChimpList;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Controller constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $id
     * @return object|null
     */
    protected function getListById(string $id): ?MailChimpList
    {
        return $this->entityManager->getRepository(MailChimpList::class)->find($id);
    }

    /**
     * Get All Lists
     *
     * @return array|null
     */
    protected function getLists() : ?array
    {
        /** @var MailChimpList[]|null $lists */
        return $this->entityManager->getRepository(MailChimpList::class)->findAll();
    }

    /**
     * @param string $listId
     * @return string
     */
    public function getMailChimpIdByListId(string $listId): string
    {
        $list = $this->getListbyId($listId);
        $mailChimpId = $list->getMailChimpId();
        return $mailChimpId ?? '';
    }

    /**
     * Return error JSON response.
     *
     * @param array|null $data
     * @param int|null $status
     * @param array|null $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(?array $data = null, ?int $status = null, ?array $headers = null): JsonResponse
    {
        return \response()->json($data ?? [], $status ?? 400, $headers ?? []);
    }

    /**
     * Remove entity from database.
     *
     * @param \App\Database\Entities\Entity $entity
     *
     * @return void
     */
    protected function removeEntity(Entity $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    /**
     * Save entity into database.
     *
     * @param \App\Database\Entities\Entity $entity
     *
     * @return void
     */
    protected function saveEntity(Entity $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Return successful JSON response.
     *
     * @param array|null $data
     * @param array|null $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successfulResponse(?array $data = null, ?array $headers = null): JsonResponse
    {
        return \response()->json($data ?? [], 200, $headers ?? []);
    }

    /**
     * Return error message for non-existing member in a list.
     *
     * @param string|null $member
     * @param string|null $list
     * @return JsonResponse
     */
    protected function errorMember(?string $member, ?string $list): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Mailchimp member: ' . $member . ' not found for given list. ' . $list],
            404
        );
    }

    /**
     * Return error message for empty members in a list.
     *
     * @param string|null $list
     * @return JsonResponse
     */
    protected function errorMembers(?string $list): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Mailchimp members not found for given list. ' . $list],
            404
        );
    }

    /**
     * Return error message for non-existing list.
     *
     * @param string|null $list
     * @return JsonResponse
     */
    protected function errorList(?string $list): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Mailchimp list not found. ' . $list],
            404
        );
    }

    /**
     * Return error message for non-existing list.
     *
     * @return JsonResponse
     */
    protected function errorLists(): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Mailchimp lists not found.'],
            404
        );
    }

    /**
     * Return error message for invalid mail chimp id.
     *
     * @param string|null $list
     * @return JsonResponse
     */
    protected function errorInvalidMailChimpId(?string $list): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Mailchimp id is invalid for given list: ' . $list],
            404
        );
    }

    /**
     * Return error message for invalid mail chimp id.
     *
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function errorInvalidPayload(?array $errors): JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Invalid data given', 'errors' => $errors],
            400
        );
    }

    /**
     * @param string $list
     * @return JsonResponse|null
     */
    protected function errorEmailSubscribed(string $list): ?JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Email exists in given list:' . $list],
            400
        );
    }

    /**
     * @param string $list
     * @return JsonResponse|null
     */
    protected function errorEmailMissing(string $list): ?JsonResponse
    {
        return $this->errorResponse(
            ['message' => 'Email missing from payload:' . $list],
            400
        );
    }
}
