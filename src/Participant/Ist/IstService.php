<?php

namespace kissj\Participant\Ist;

use kissj\FlashMessages\FlashMessagesBySession;
use kissj\Mailer\PhpMailerWrapper;
use kissj\Participant\Admin\StatisticValueObject;
use kissj\Payment\PaymentRepository;
use kissj\Payment\PaymentService;
use kissj\User\User;
use kissj\User\UserService;

class IstService {
    private $istRepository;
    private $paymentRepository;
    private $userService;
    private $paymentService;
    private $flashMessages;
    private $mailer;

    public function __construct(
        IstRepository $istRepository,
        PaymentRepository $paymentRepository,
        UserService $userService,
        PaymentService $paymentService,
        FlashMessagesBySession $flashMessages,
        PhpMailerWrapper $mailer
    ) {
        $this->istRepository = $istRepository;
        $this->paymentRepository = $paymentRepository;
        $this->userService = $userService;
        $this->paymentService = $paymentService;
        $this->flashMessages = $flashMessages;
        $this->mailer = $mailer;
    }

    public function getIst(User $user): Ist {
        if ($this->istRepository->countBy(['user' => $user]) === 0) {
            $ist = new Ist();
            $ist->user = $user;
            $this->istRepository->persist($ist);
        }

        return $this->istRepository->findOneBy(['user' => $user]);
    }

    public function addParamsIntoIst(Ist $ist, array $params): Ist {
        $ist->firstName = $params['firstName'] ?? null;
        $ist->lastName = $params['lastName'] ?? null;
        $ist->nickname = $params['nickname'] ?? null;
        if ($params['birthDate'] !== null) {
            $ist->birthDate = new \DateTime($params['birthDate']);
        }
        $ist->gender = $params['gender'] ?? null;
        $ist->email = $params['email'] ?? null;
        $ist->telephoneNumber = $params['telephoneNumber'] ?? null;
        $ist->permanentResidence = $params['permanentResidence'] ?? null;
        $ist->country = $params['country'] ?? null;
        $ist->scoutUnit = $params['scoutUnit'] ?? null;
        $ist->setTshirt($params['tshirtShape'] ?? null, $params['tshirtSize'] ?? null);
        $ist->foodPreferences = $params['foodPreferences'] ?? null;
        $ist->healthProblems = $params['healthProblems'] ?? null;
        $ist->languages = $params['languages'] ?? null;
        $ist->swimming = $params['swimming'] ?? null;
        $ist->driversLicense = $params['driversLicense'] ?? null;
        $ist->skills = $params['skills'] ?? null;
        $ist->preferredPosition = $params['preferredPosition'] ?? [];
        $ist->notes = $params['notes'] ?? null;

        return $ist;
    }

    public function isIstValidForClose(Ist $ist): bool {
        if (
            $ist->firstName === null
            || $ist->lastName === null
            || $ist->birthDate === null
            || $ist->gender === null
            || $ist->email === null
            || $ist->telephoneNumber === null
            || $ist->permanentResidence === null
            || $ist->country === null
            || $ist->scoutUnit === null
            || $ist->foodPreferences === null
            || $ist->languages === null
            || $ist->swimming === null
            || $ist->driversLicense === null
            || $ist->preferredPosition === null
            || $ist->getTshirtShape() === null
            || $ist->getTshirtSize() === null
        ) {
            return false;
        }

        if (!empty($ist->email) && filter_var($ist->email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return true;
    }

    public function isCloseRegistrationValid(Ist $ist): bool {
        if (!$this->isIstValidForClose($ist)) {
            $this->flashMessages->warning('Cannot lock the registration - some details are wrong or missing (probably email or some date)');

            return false;
        }
        if ($this->userService->getClosedIstsCount() >= $ist->user->event->maximalClosedIstsCount) {
            $this->flashMessages->warning('For IST we have full registration now and you are below the bar, so we cannot register you yet. Please wait for limit rise');

            return false;
        }

        return true;
    }

    public function closeRegistration(Ist $ist): Ist {
        if ($this->isCloseRegistrationValid($ist)) {
            $this->userService->closeRegistration($ist->user);
            $this->mailer->sendRegistrationClosed($ist->user);
        }

        return $ist;
    }

    public function getAllIstsStatistics(): StatisticValueObject {
        $ists = $this->istRepository->findAll();

        return new StatisticValueObject($ists);
    }

    public function getAllClosedIsts(): array {
        /** @var Ist[] $ists */
        $ists = $this->istRepository->findBy(['role' => User::ROLE_IST], ['id' => false]); // TODO fix order (reversed)

        $closedIsts = [];
        foreach ($ists as $ist) {
            if ($ist->user->status === User::STATUS_CLOSED) {
                $closedIsts[] = $ist;
            }
        }

        return $closedIsts;
    }

    public function openRegistration(Ist $ist, $reason): Ist {
        $this->mailer->sendDeniedRegistration($ist, $reason);
        $this->userService->openRegistration($ist->user);

        return $ist;
    }

    public function approveRegistration(Ist $ist): Ist {
        $price = $this->paymentService->getPrice($ist);
        $payment = $this->paymentRepository->createAndPersistNewPayment($ist, $price);

        $this->mailer->sendRegistrationApprovedWithPayment($ist, $payment);
        $this->userService->approveRegistration($ist->user);

        return $ist;
    }

    // TODO fix

    public function getAllApprovedIstsWithPayment(): array {
        $approvedIsts = $this->roleRepository->findBy([
            'event' => $this->eventName,
            'name' => 'ist',
            'status' => 'approved',
        ]);
        $ists = [];
        /** @var Role $approvedIst */
        foreach ($approvedIsts as $approvedIst) {
            $ist['info'] = $this->istRepository->findOneBy(['user' => $approvedIst->user]);
            $ist['payment'] = $this->getOneValidPayment($ist['info']);
            // TODO discuss moving this piece of logic elsewhere
            $ist['elapsedPaymentDays'] = $ist['payment']->generatedDate->diff(new \DateTime());
            $ist['elapsedPaymentDays'] = $ist['elapsedPaymentDays']->days;
            $ists[] = $ist;
        }

        return $ists;
    }
}
