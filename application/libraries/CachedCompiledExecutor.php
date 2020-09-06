<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'CompiledExecutor.php';

abstract class CachedCompiledExecutor extends CompiledExecutor
{
    protected abstract function getCompiledFileName(): string;

    public function execute(): ExecutionResult
    {
        if ($this->isCompiledFileCached()) {
            $this->restoreCompiledFileFromCache();
        } else {
            $this->compile();
            $this->cacheCompiledFile();
        }
        return $this->run();
    }

    private function isCompiledFileCached(): bool
    {
        return FileCache::file_exists($this->getCompiledFileCacheKey());
    }

    private function restoreCompiledFileFromCache(): void
    {
        file_put_contents($this->getCompiledFileName(), FileCache::file_get_contents($this->getCompiledFileCacheKey()));

        exec('sudo chmod +x ' . $this->getCompiledFileName());
        exec('sudo chown ' . $this->sandbox->userName . ':jobe ' . $this->getCompiledFileName());
    }

    private function getCompiledFileCacheKey(): string
    {
        return md5('__COMPILED__ ' . $this->task->sourceCode);
    }

    private function cacheCompiledFile(): void
    {
        FileCache::file_put_contents($this->getCompiledFileCacheKey(), file_get_contents($this->getCompiledFileName()));
    }
}