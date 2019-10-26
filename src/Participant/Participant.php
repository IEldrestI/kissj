<?php

namespace kissj\Participant;

use kissj\Orm\EntityDatetime;
use kissj\User\User;

/**
 * Master table for all participants, using Single Table Inheritance
 * All commons are here, entitis are seaprated of course (:
 *
 * @property int         $id
 * @property User|null   $user      m:hasOne
 * @property string|null $role      needed for DB working faster
 * @property string|null $firstName
 * @property string|null $lastName
 * @property string|null $nickname
 * @property string|null $permanentResidence
 * @property string|null $telephoneNumber
 * @property string|null $gender
 * @property string|null $country
 * @property string|null $email
 * @property string|null $scoutUnit
 * @property string|null $languages
 * @property string|null $birthDate m:passThru(dateFromString|dateToString)
 * @property string|null $birthPlace
 * @property string|null $healthProblems
 * @property string|null $foodPreferences
 * @property string|null $idNumber
 * @property string|null $scarf
 * @property string|null $tshirt
 * @property string|null $notes
 */
class Participant extends EntityDatetime {
    public function setUser(User $user): void {
        // else  LeanMapper \ Exception \ MemberAccessException
        // Cannot write to read-only property 'user' in entity kissj\Participant\Participant.
        $this->row->user_id = $user->id;
        $this->row->cleanReferencedRowsCache('user', 'user_id');
    }
}
