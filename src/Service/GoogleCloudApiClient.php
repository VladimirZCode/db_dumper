<?php declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\DumpFile;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Google\Service\Drive\DriveFile;
use Google\Http\MediaFileUpload;
use GuzzleHttp\Psr7\Request as GuzzleHttpRequest;

class GoogleCloudApiClient implements CloudApiClientInterface
{
    private OutputInterface $output;
    private DumpFile $dumpFile;
    private GoogleClient $googleClient;
    private GoogleDrive $driveService;

    public function __construct(
        GoogleClient $googleClient
    ) {
        $this->googleClient = $googleClient;
        $this->driveService = new GoogleDrive($this->googleClient);
    }

    public function upload(): bool
    {
        $this->output("Dump file upload started.");

        if (!$this->configureGoogleClient()) {
            return false;
        }

        $driveFileRequest = $this->createDriveFileRequest();

        $chunkSizeBytes = (int)$_ENV['DB_DUMP_FILE_CHUNK_SIZE_B'];

        $media = $this->createMediaFileUpload($driveFileRequest, $chunkSizeBytes);
        $media->setFileSize(filesize($this->dumpFile->getFullPath()));

        $handle = fopen($this->dumpFile->getFullPath(), "rb");
        $status = $this->uploadFile($handle, $chunkSizeBytes, $media);
        fclose($handle);

        if (!$status) {
            $this->output("There was a problem with file upload!");

            return false;
        }

        $this->output("File upload finished successfully.");

        return true;
    }

    public function setOutput(OutputInterface $output): CloudApiClientInterface
    {
        $this->output = $output;
        return $this;
    }

    public function setDumpFile(DumpFile $dumpFile): CloudApiClientInterface
    {
        $this->dumpFile = $dumpFile;
        return $this;
    }

    protected function output(string $str): void
    {
        if (!($this->output instanceof OutputInterface)) {
            return;
        }

        $this->output->writeln($str);
    }

    private function configureGoogleClient(): bool
    {
        $this->googleClient->setApplicationName($_ENV['CLOUD_API_APP_NAME']);
        if ($googleCloudKeys = $this->getGoogleServiceAccountKeysFilePath()) {
            $this->googleClient->setAuthConfig($googleCloudKeys);
        } else {
            $this->output("The GOOGLE_SERVICE_ACCOUNT_KEYS_FILE_PATH setting is not configured!");

            return false;
        }

        $this->googleClient->addScope($_ENV['GOOGLE_CLIENT_SCOPE']);
        $this->googleClient->setDefer(true);

        return true;
    }

    /**
     * @return GuzzleHttpRequest
     */
    private function createDriveFileRequest()
    {
        $file = new DriveFile();
        $file->setName($this->dumpFile->getName());
        $file->setParents([$_ENV['DB_DUMP_FOLDER_ID']]);

        return $this->driveService->files->create($file);
    }

    private function createMediaFileUpload(
        GuzzleHttpRequest $driveFileRequest,
        int $chunkSizeBytes
    ): MediaFileUpload {
        $media = new MediaFileUpload(
            $this->googleClient,
            $driveFileRequest,
            'text/plain',
            null,
            true,
            $chunkSizeBytes
        );

        return $media;
    }

    private function getGoogleServiceAccountKeysFilePath(): ?string
    {
        $keysFilePath = __DIR__ . $_ENV['GOOGLE_SERVICE_ACCOUNT_KEYS_FILE_PATH'];

        return file_exists($keysFilePath) ? $keysFilePath : null;
    }

    /**
     * Uploads the file in chunks. $status will be false until the process is complete.
     * @param resource $fileHandle
     * @param int $chunkSizeBytes
     * @param MediaFileUpload $media
     * @return DriveFile|false
     */
    private function uploadFile($fileHandle, int $chunkSizeBytes, MediaFileUpload $media)
    {
        $status = false;
        $chunkNumber = 1;
        while (!$status && !feof($fileHandle)) {
            // Read until you get $chunkSizeBytes from the file.
            // fread will never return more than 8192 bytes if the stream is read
            // buffered and it does not represent a plain text file.
            $chunk = $this->readFileChunk($fileHandle, $chunkSizeBytes);
            $this->output("Chunk number: $chunkNumber read from the file.");
            $status = $media->nextChunk($chunk);
            $this->output("Chunk number: $chunkNumber uploaded.");
            $chunkNumber++;
        }

        return $status;
    }

    private function readFileChunk($fileHandle, int $chunkSize): string
    {
        $byteCount = 0;
        $fullChunk = "";
        while (!feof($fileHandle)) {
            // fread will never return more than 8192 bytes if the stream is read
            // buffered and it does not represent a plain file
            $chunk = fread($fileHandle, (int)$_ENV['DB_DUMP_READ_BYTES_NUM']);
            $byteCount += strlen($chunk);
            $fullChunk .= $chunk;
            if ($byteCount >= $chunkSize) {
                return $fullChunk;
            }
        }

        return $fullChunk;
    }
}
