<?php
/**
 * @author Zemlyansky Alexander <yuoanswami@gmail.com>
 */

namespace AssetUnion;

/**
 * AssetUnion
 * Joining of files into a single output file, i.e css or js files
 * Auto rebuilding the output file if any involved file has been modified after the last rebuilding
 */
class AssetUnion {

    /** @var array Source filenames which are to be united in a single file */
    public readonly array $filenames;

    /** @var string Full path to a resulting file */
    public readonly string $outputFile;

    /** @var string Base path to source files  */
    public readonly ?string $assetPath;

    /** @var string|null Result of output data if has been rebuilt */
    public ?string $resultingData;

    /** @var array|null Config data (by now just the asset path parameter) */
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

    /**
     * Forms the output data if there were changes or file not exist
     * @return $this
     */
    public function rebuildIfNeeded(): self {
        if ($this->needsRebuild()) {
            return $this->rebuild();
        }

        return $this;
    }

    /**
     * Forms the output data
     * @return $this
     */
    public function rebuild(): self {
        $data = [];
        foreach ($this->filenames as $filename) {
            $data[] = file_get_contents($this->assetPath . '/' . $filename);
        }

        $data = join("\n", $data);
        $this->resultingData = $data;

        return $this;
    }

    /**
     * Saves data to the output file
     * @return bool
     */
    public function save(): bool {
        return isset($this->resultingData) ? file_put_contents($this->outputFile, $this->resultingData) : false;
    }

    /**
     * Transforms data as specified in the closure
     * @param \Closure $modifier Function with accepts data and returns its transformed version as needed
     * @return $this
     */
    public function modifyResult(\Closure $modifier): self {
        if (isset($this->resultingData)) {
            $this->resultingData = $modifier($this->resultingData);
        }

        return $this;
    }

    /**
     * Whether the output file must be rebuilt (i.e. any involed file has changed after the last rebuilding)
     * @return bool
     */
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

    /**
     * Whether a particular file is in list of source files
     * @param $filename source file name
     * @return bool
     */
    public function containsFile($filename): bool {
        return in_array($filename, $this->filenames);
    }

}
