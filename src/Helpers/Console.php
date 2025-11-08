<?php

namespace DatabaseBackup\Helpers;

class Console
{
	private static string $baseDirectory;
	private static bool $FORCE_OUTPUT = FALSE;

	public static function getPrefix(): string
	{
		$stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$callerFile = '';
		$baseFile = basename(__FILE__);

		// 1. Find the first file in the stack that is *not* this Console class
		foreach ($stackTrace as $frame) {
			if (isset($frame['file']) && basename($frame['file']) !== $baseFile) {
				$callerFile = $frame['file'];
				break;
			}
		}

		// If no caller is found, default to an empty prefix
		if (!$callerFile) {
			return '[] ';
		}

		// 2. Determine the project's root/base directory on first run
		if (!isset(self::$baseDirectory)) {
			// A more robust way: use the directory of the first file in the trace
			// which is likely the main entry script (e.g., server.php) or a file 
			// close to the root. For simplicity, we'll try to find the project root 
			// by looking for 'vendor'.
			$rootPath = dirname($callerFile);
			while (!file_exists($rootPath . '/vendor') && strlen($rootPath) > 1) {
				$rootPath = dirname($rootPath);
			}
			self::$baseDirectory = $rootPath;
		}

		// 3. Trim the base path and 'src' directory from the caller file path
		$prefix = str_replace([self::$baseDirectory, 'src' . DIRECTORY_SEPARATOR], '', $callerFile);

		// Remove file extension
		$prefix = preg_replace('/\.[^.]+$/', '', $prefix);

		// Normalize slashes (optional, but good practice)
		$prefix = str_replace(DIRECTORY_SEPARATOR, '/', $prefix);

		return sprintf('[%s] ', $prefix);

	}

	public static function forceOutput(bool $state): void {
		self::$FORCE_OUTPUT = $state;
	}

	public static function comment(string $message): void
	{
		self::writeWithTimestamp(self::withColor('0;33', $message));
	}

	public static function info(string $message): void
	{
		self::writeWithTimestamp(self::withColor('0;32', $message));
	}

	public static function question(string $message): void
	{
		self::writeWithTimestamp(self::withColor('0;36', $message));
	}

	public static function error(string $message): void
	{
		self::writeWithTimestamp(self::withColor('0;31', $message));
	}

	public static function lightRed(string $message): void
	{
		self::writeWithTimestamp(self::withColor('1;31', $message));
	}

	public static function lightGreen(string $message): void
	{
		self::writeWithTimestamp(self::withColor('1;32', $message));
	}

	public static function lightCyan(string $message): void
	{
		self::writeWithTimestamp(self::withColor('1;36', $message));
	}

	public static function echo(string $message): void
	{
		self::writeWithTimestamp($message);
	}

	public static function write(string $message): void
	{
		self::writeWithTimestamp($message, false);
	}

	public static function writeln(string $message): void
	{
		self::writeWithTimestamp($message);
	}

	private static function writeWithTimestamp(string $message, bool $newLine = true): void
	{
		$message = self::prependTime(self::getPrefix() . $message);
		self::writeWithoutTimestamp($message, $newLine, false);
	}

	private static function writeWithoutTimestamp(string $message, bool $newLine = true, bool $prefix = true): void                                     
	{
		$message = $prefix ? self::getPrefix() . $message : $message;

		if (self::$FORCE_OUTPUT === TRUE || (self::$FORCE_OUTPUT === FALSE && posix_isatty(\STDOUT))) {         
			echo $message . ($newLine ? PHP_EOL : null);
		}
	}

	protected static function withColor(string $code, string $message): string
	{
		return sprintf("\033[%sm%s\033[0m", $code, $message);
	}

	protected static function prependTime(string $message): string
	{
		return date('[Y-m-d H:i:s]') . " $message";
	}
}
