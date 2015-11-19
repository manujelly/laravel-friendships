<?php

namespace Hootlex\Friendships\Traits;

use Hootlex\Friendships\Models\Friendship;
use Hootlex\Friendships\Status;
use Illuminate\Database\Eloquent\Model;

trait Friendable
{
    /**
     * @param Model $recipient
     *
     * @return Frienship|false
     */
    public function befriend(Model $recipient)
    {
        if (!$this->canBefriend($recipient)) {
            return false;
        }

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->friends()->save($friendship);

        return $friendship;

    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function unfriend(Model $recipient)
    {

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function isFriendWith(Model $recipient)
    {
        return $this->findFriendship($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function acceptFriendRequest(Model $recipient)
    {
        return $this->findFriendship($recipient)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function denyFriendRequest(Model $recipient)
    {

        return $this->findFriendship($recipient)->update([
            'status' => Status::DENIED,
        ]);
    }

    /**
     * @param Model $recipient
     *
     * @return Friendship
     */
    public function blockFriend(Model $recipient)
    {
        //if there is a friendship between two users delete it
        $this->findFriendship($recipient)->delete();

        $friendship = (new Friendship)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ]);

        return $this->friends()->save($friendship);
    }

    /**
     * @param Model $recipient
     */
    public function unblockFriend(Model $recipient)
    {

        return $this->findFriendship($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function getFriendship(Model $recipient)
    {
        return $this->findFriendship($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     * @internal param int $limit
     * @internal param null $offset
     *
     */
    public function getAllFriendships()
    {
        return $this->findFriendships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getPendingFriendships()
    {
        return $this->findFriendships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAcceptedFriendships()
    {
        return $this->findFriendships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedFriendships()
    {
        return $this->findFriendships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFriendships()
    {
        return $this->findFriendships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlocked(Model $recipient)
    {
        return $this->friends()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedBy(Model $recipient)
    {
        return $recipient->hasBlocked($this);
    }

    /**
     * @return mixed
     */
    public function getFriendRequests()
    {
        return Friendship::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return boolean
     */
    public function canBefriend($recipient)
    {
        //If sender has a friendship with the recipient return false
        if ($this->getFriendship($recipient)) {
            return false;
        }
        return true;
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    private function findFriendship(Model $recipient)
    {
        return Friendship::betweenModels($this, $recipient);
    }

    /**
     * @param $status
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFriendships($status = '%')
    {
        return Friendship::where('status', 'LIKE', $status)
            ->where(function ($q) {
                $q->whereSender($this);
            })
            ->orWhere(function ($q) {
                $q->whereRecipient($this);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function friends()
    {
        return $this->morphMany(Friendship::class, 'sender');
    }

}