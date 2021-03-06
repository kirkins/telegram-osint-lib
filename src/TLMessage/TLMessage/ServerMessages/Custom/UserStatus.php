<?php

declare(strict_types=1);

namespace TelegramOSINT\TLMessage\TLMessage\ServerMessages\Custom;

use TelegramOSINT\Client\StatusWatcherClient\Models\HiddenStatus;
use TelegramOSINT\Exception\TGException;
use TelegramOSINT\MTSerialization\AnonymousMessage;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusEmpty;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusLastMonth;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusLastWeek;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusOffline;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusOnline;
use TelegramOSINT\TLMessage\TLMessage\ServerMessages\UserStatus\UserStatusRecently;
use TelegramOSINT\TLMessage\TLMessage\TLServerMessage;

class UserStatus extends TLServerMessage
{
    /**
     * @var UserStatusEmpty|UserStatusOffline|UserStatusOnline|UserStatusLastWeek
     */
    private $status;

    public function __construct(AnonymousMessage $tlMessage)
    {
        parent::__construct($tlMessage);

        if(UserStatusOnline::isIt($tlMessage))
            $this->status = new UserStatusOnline($tlMessage);
        if(UserStatusOffline::isIt($tlMessage))
            $this->status = new UserStatusOffline($tlMessage);
        if(UserStatusEmpty::isIt($tlMessage))
            $this->status = new UserStatusEmpty($tlMessage);
        if(UserStatusRecently::isIt($tlMessage))
            $this->status = new UserStatusRecently($tlMessage);
        if(UserStatusLastWeek::isIt($tlMessage))
            $this->status = new UserStatusLastWeek($tlMessage);
    }

    /**
     * @param AnonymousMessage $tlMessage
     *
     * @return bool
     */
    public static function isIt(AnonymousMessage $tlMessage): bool
    {
        return
            UserStatusOnline::isIt($tlMessage) ||
            UserStatusOffline::isIt($tlMessage) ||
            UserStatusEmpty::isIt($tlMessage) ||
            UserStatusRecently::isIt($tlMessage) ||
            UserStatusLastWeek::isIt($tlMessage) ||
            UserStatusLastMonth::isIt($tlMessage);
    }

    public function isOnline(): bool
    {
        return UserStatusOnline::isIt($this->getTlMessage());
    }

    public function isOffline(): bool
    {
        return UserStatusOffline::isIt($this->getTlMessage());
    }

    public function isHidden(): bool
    {
        return
            UserStatusEmpty::isIt($this->getTlMessage()) ||
            UserStatusRecently::isIt($this->getTlMessage()) ||
            UserStatusLastWeek::isIt($this->getTlMessage()) ||
            UserStatusLastMonth::isIt($this->getTlMessage());
    }

    /**
     * @throws TGException
     *
     * @return HiddenStatus
     */
    public function getHiddenState(): HiddenStatus
    {
        if(UserStatusRecently::isIt($this->getTlMessage()))
            return new HiddenStatus(HiddenStatus::HIDDEN_SEEN_RECENTLY);

        if(UserStatusLastWeek::isIt($this->getTlMessage()))
            return new HiddenStatus(HiddenStatus::HIDDEN_SEEN_LAST_WEEK);

        if(UserStatusLastMonth::isIt($this->getTlMessage()))
            return new HiddenStatus(HiddenStatus::HIDDEN_SEEN_LAST_MONTH);

        if(UserStatusEmpty::isIt($this->getTlMessage()))
            return new HiddenStatus(HiddenStatus::HIDDEN_EMPTY);

        throw new TGException(TGException::ERR_ASSERT_UNKNOWN_HIDDEN_STATUS);
    }

    public function getExpires(): ?int
    {
        return $this->isOnline() ? $this->status->getExpires() : null;
    }

    public function getWasOnline(): ?int
    {
        return $this->isOffline() ? $this->status->getWasOnline() : null;
    }
}
