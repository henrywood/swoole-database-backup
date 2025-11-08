<?php

namespace DatabaseBackup;

enum DatabaseDriver
{
	case MYSQL;
	case POSTGRES;
	case SQLITE;

	public function port(): int
	{
		return match ($this) {
		self::MYSQL		=> 3306,		               
			self::POSTGRES	=> 5432,
			self::SQLITE	=> 0
	};
	}
}   

