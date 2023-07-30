<?php

namespace AssetUnion;

class AssetUnion {

    public readonly array $filenames;
    public readonly string $outputFile;
    public readonly string $assetPath;
    public readonly ?string $resultingData;
    protected readonly ?array $config;

    public function __construct(array $filenames, $config = null) {
        $this->filenames = $filenames;
        $this->config = $config ?? require(__DIR__ . '/config.php');
        $this->assetPath = $this->config['source_dir'];
    }

    public function setOutput($outputFile): self {
        $this->outputFile = $outputFile;
        return $this;
    }

    public function rebuildIfNeeded(): self {
        if ($this->needsRebuild()) {
            Log::info('needs rebuild');

            return $this->rebuild();
        }

        return $this;
    }

    public function rebuild(): self {
        $data = [];
        foreach ($this->filenames as $filename) {
            $data[] = file_get_contents($this->assetPath . '/' . $filename);
        }

        $data = join("\n", $data);
        $this->resultingData = $data;

        return $this;
    }

    public function save(): bool {
        return isset($this->resultingData) ? file_put_contents($this->outputFile, $this->resultingData) : false;
    }

    public function modifyResult(\Closure $modifier): self {
        $this->resultingData = isset($this->resultingData) ? $modifier($this->resultingData) : null;

        return $this;
    }

    public function needsRebuild(): bool {
        if (file_exists($this->outputFile)) {
            $outputFileTime = filemtime($this->outputFile);
        } else {
            return true;
        }

        foreach ($this->filenames as $filename) {
            $filemtime = filemtime($this->assetPath . '/' . $filename);
            if ($filemtime && $filemtime > $outputFileTime) {
                return true;
            }
        }

        return false;
    }

    public function containsFile($filename): bool {
        return in_array($filename, $this->filenames);
    }

}
