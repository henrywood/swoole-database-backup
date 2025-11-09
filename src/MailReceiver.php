<?php

namespace DatabaseBackup;

class MailReceiver
{
    public function __construct(
        public string $email,
        public string $name,
    )
    {
    }
}
