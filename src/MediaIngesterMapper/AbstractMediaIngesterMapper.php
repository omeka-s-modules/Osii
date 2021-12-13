<?php
namespace Osii\MediaIngesterMapper;

use Osii\Entity\OsiiImport;

abstract class AbstractMediaIngesterMapper implements MediaIngesterMapperInterface
{
    protected $importEntity;

    public function __construct(OsiiImport $importEntity)
    {
        $this->importEntity = $importEntity;
    }

    /**
     * Get the ingest_url from the o:original_url.
     *
     * Assumes a relative URL is the result of a custom `file_store.local.base_uri`
     * configuration and builds the absolute URL from the relative URL using the
     * root endpoint.
     *
     * @param string $originalUrl The o:original_url
     * @return string The ingest_url
     */
    public function getIngestUrl($originalUrl)
    {
        $parsedUrl = parse_url($originalUrl);
        if (isset($parsedUrl['host'])) {
            // This is an absolute URL.
            return $originalUrl;
        }
        // This is a relative URL.
        $parsedUrl = parse_url($this->importEntity->getRootEndpoint());
        return sprintf('%s://%s%s', $parsedUrl['scheme'], $parsedUrl['host'], $originalUrl);
    }
}
