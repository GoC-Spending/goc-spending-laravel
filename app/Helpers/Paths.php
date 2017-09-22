<?php


namespace App\Helpers;

class Paths
{

    /**
     * Get the path to the raw data folder.
     *
     * @return string  The path to the raw data folder, with a trailing slash.
     */
    public static function getSourceDirectory()
    {
        return storage_path() . '/' . env('FETCH_RAW_HTML_FOLDER', 'raw-data') . '/';
    }

    /**
     * Get the path to the raw data folder for a given department.
     *
     * @param $acronym string  The department's acronym.
     *
     * @return string  The path to the department's raw data folder.
     */
    public static function getSourceDirectoryForDepartment($acronym)
    {
        return Paths::getSourceDirectory() . $acronym;
    }

    /**
     * Get the path to the metadata folder.
     *
     * @return string  The path to the metadata folder, with a trailing slash.
     */
    public static function getMetadataDirectory()
    {
        return storage_path() . '/' . env('FETCH_METADATA_FOLDER', 'metadata') . '/';
    }

    /**
     * Get the path to the metadata folder for a given department.
     *
     * @param $acronym string  The department's acronym.
     *
     * @return string  The path to the department's metadata folder.
     */
    public static function getMetadataDirectoryForDepartment($acronym)
    {
        return Paths::getMetadataDirectory() . $acronym;
    }

    /**
     * Generate a unique filename based on a contract URL.
     *
     * Note: The contract URL should be a "permalink" version. Usually
     *       this means you’ll need to remove any session information
     *       or other data that isn’t directly related to that contract.
     *
     * @param string $url        The URL for the contract.
     * @param string $extension  The extension to use for the filename.
     *
     * @return string  The filename based on the contract information.
     */
    public static function generateFilenameFromUrl($url, $extension = '.html')
    {
        return md5($url) . $extension;
    }
}
