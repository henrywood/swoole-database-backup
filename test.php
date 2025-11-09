<?php
require_once('vendor/autoload.php');

use DatabaseBackup\AbstractBackup; // Import base class
use DatabaseBackup\Backup;
use DatabaseBackup\DatabaseConnection; // Import DatabaseConnection
use DatabaseBackup\DatabaseDriver; // Import DatabaseDriver
use DatabaseBackup\MailReceiver;
use DatabaseBackup\Helpers\Console;
use Swoole\Runtime;
use Swoole\Http\Server; // Import Server class

// Define the path to your SQLite database file.
// IMPORTANT: This should be an absolute path for reliability.
const DB_FILE = __DIR__ . '/db.db'; 
const DB_BACKUP_INTERVAL_SECS = 10;

// --- Backup Class Definition ---
// In a real application, this would be in DatabaseBackup\Backups\NucleusBackup.php
class NucleusBackup extends AbstractBackup
{
	protected bool $sendMailOnError = false;
	protected bool $sendMailOnSuccess = false;

	// Interval in milliseconds (2_000 ms = 2 seconds for testing)
	public function interval(): int
	{
		return (DB_BACKUP_INTERVAL_SECS * 1000);
	}

	public function filePath(): string
	{
		// Save the backup file to a temporary location.
		// I've changed dirname(__DIR__, 2) to a simpler temp path for local testing.
		return sprintf('/tmp/nucleus-%s.sql', uniqid());
	}

	public function onSuccess(string $path, callable $done): void
	{
		$done();
		Console::info('nucleus backup completed');

		// TODO: Upload to AWS S3 here

		// Clean up
		Console::comment(sprintf('Cleaning up backup file: %s', $path));
		Console::comment(sprintf('DB backup size: %d bytes', filesize($path)));
		//unlink($path);
	}

	public function onError(\Throwable $exception): void
	{
		Console::error(sprintf('Nucleus backup failed: %s', $exception->getMessage()));
	}

	public function connection(): DatabaseConnection
	{
		return new DatabaseConnection(		   
			driver: DatabaseDriver::SQLITE,
			host: 'localhost',
			username: '',
			password: '',
			database: DB_FILE // Use the defined absolute path
		);
	}
}
// -----------------------------

function startDBBackupInBackground(): void {

	$receivers = [
		new MailReceiver(
			email: 'henry.wood.dk@gmail.com',      
			name: 'Jane Doe'
		),
	];

	// You can remove the mail receivers if sendMailOnError/Success are false, 
	// but leaving it doesn't hurt.
	Backup::new()
		->withMailReceivers($receivers) 
		->startPeriodic([NucleusBackup::class]);
}

Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

$server = new Server("127.0.0.1", 9505);

// The HTTP server needs a request handler, otherwise it won't do much
$server->on('request', function ($request, $response) {
	$response->end("Database Backup Service Running...");
});

// The start event is called when the Swoole Master process starts
$server->on('start', function (Server $server) {
	Console::info("Swoole HTTP Server started at http://127.0.0.1:9505");

	// Start the backup process in a new coroutine
	// The `go()` function creates a coroutine for the background task
	go(function() {    
		Console::info("Starting Database Backup Service...");
		startDBBackupInBackground();
	});
});

$server->start();


