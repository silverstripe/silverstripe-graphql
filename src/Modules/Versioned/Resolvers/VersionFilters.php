<?php

namespace SilverStripe\GraphQL\Resolvers;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\RelationList;
use SilverStripe\Versioned\Mode\Versioned;
use InvalidArgumentException;
use DateTime;

class VersionFilters
{
    /**
     * Use this as a fallback where resolver results aren't queried as a DataList,
     * but rather use DataObject::get_one(). Example: SiteTree::get_by_link().
     * Note that the 'status' and 'version' modes are not supported.
     * Wrap this call in {@link Versioned::withVersionedMode()} in order to avoid side effects.
     *
     * @param $versioningArgs
     * @return null|void
     */
    public function applyToReadingState(array $versioningArgs)
    {
        if (!isset($versioningArgs['mode'])) {
            return null;
        }

        $this->validateArgs($versioningArgs);

        $mode = $versioningArgs['mode'];
        switch ($mode) {
            case Versioned::LIVE:
            case Versioned::DRAFT:
                Versioned::set_stage($mode);
                break;
            case 'archive':
                $date = $versioningArgs['archiveDate'];
                Versioned::set_reading_mode($mode);
                Versioned::reading_archived_date($date);
                break;
        }
    }

    /**
     * @template T of DataObject
     * @param DataList<T> $list
     * @param array $versioningArgs
     * @throws InvalidArgumentException
     * @return DataList<T>
     */
    public function applyToList(DataList $list, array $versioningArgs): DataList
    {
        if ($list instanceof RelationList) {
            throw new InvalidArgumentException(sprintf(
                'Version filtering cannot be applied to instances of %s. Are you using the plugin on a nested query?',
                get_class($list)
            ));
        }
        if (!isset($versioningArgs['mode'])) {
            return $list;
        }

        $this->validateArgs($versioningArgs);

        $mode = $versioningArgs['mode'];
        switch ($mode) {
            case Versioned::LIVE:
            case Versioned::DRAFT:
                $list = $list
                    ->setDataQueryParam('Versioned.mode', 'stage')
                    ->setDataQueryParam('Versioned.stage', $mode);
                break;
            case 'archive':
                $date = $versioningArgs['archiveDate'];
                $list = $list
                    ->setDataQueryParam('Versioned.mode', 'archive')
                    ->setDataQueryParam('Versioned.date', $date);
                break;
            case 'all_versions':
                $list = $list->setDataQueryParam('Versioned.mode', 'all_versions');
                break;
            case 'latest_versions':
                $list = $list->setDataQueryParam('Versioned.mode', 'latest_versions');
                break;
            case 'status':
                // When querying by Status we need to ensure both stage / live tables are present
                /* @var DataObject&Versioned $sng */
                $sng = singleton($list->dataClass());
                $baseTable = $sng->baseTable();
                $liveTable = $sng->stageTable($baseTable, Versioned::LIVE);
                $statuses = $versioningArgs['status'];

                // If we need to search archived records, we need to manually join draft table
                if (in_array('archived', $statuses ?? [])) {
                    $list = $list
                        ->setDataQueryParam('Versioned.mode', 'latest_versions');
                    // Join a temporary alias BaseTable_Draft, renaming this on execution to BaseTable
                    // See Versioned::augmentSQL() For reference on this alias
                    $draftTable = $sng->stageTable($baseTable, Versioned::DRAFT) . '_Draft';
                    $list = $list
                        ->leftJoin(
                            $draftTable,
                            "\"{$baseTable}\".\"ID\" = \"{$draftTable}\".\"ID\""
                        );
                } else {
                    // Use draft as base query mode (base join live)
                    $draftTable = $baseTable;
                    $list = $list
                        ->setDataQueryParam('Versioned.mode', 'stage')
                        ->setDataQueryParam('Versioned.stage', Versioned::DRAFT);
                }

                // Always include live table
                $list = $list->leftJoin(
                    $liveTable,
                    "\"{$baseTable}\".\"ID\" = \"{$liveTable}\".\"ID\""
                );

                // Add all conditions
                $conditions = [];

                // Modified exist on both stages, but differ
                if (in_array('modified', $statuses ?? [])) {
                    $conditions[] = "\"{$liveTable}\".\"ID\" IS NOT NULL AND \"{$draftTable}\".\"ID\" IS NOT NULL"
                        . " AND \"{$draftTable}\".\"Version\" <> \"{$liveTable}\".\"Version\"";
                }

                // Is deleted and sent to archive
                if (in_array('archived', $statuses ?? [])) {
                    // Note: Include items staged for deletion for the time being, as these are effectively archived
                    // we could split this out into "staged for deletion" in the future
                    $conditions[] = "\"{$draftTable}\".\"ID\" IS NULL";
                }

                // Is on draft only
                if (in_array('draft', $statuses ?? [])) {
                    $conditions[] = "\"{$liveTable}\".\"ID\" IS NULL AND \"{$draftTable}\".\"ID\" IS NOT NULL";
                }

                if (in_array('published', $statuses ?? [])) {
                    $conditions[] = "\"{$liveTable}\".\"ID\" IS NOT NULL";
                }

                // Validate that all statuses have been handled
                if (empty($conditions) || count($statuses ?? []) !== count($conditions ?? [])) {
                    throw new InvalidArgumentException("Invalid statuses provided");
                }
                $list = $list->whereAny(array_filter($conditions ?? []));
                break;
            case 'version':
                // Note: Only valid for ReadOne
                $list = $list->setDataQueryParam([
                    "Versioned.mode" => 'version',
                    "Versioned.version" => $versioningArgs['version'],
                ]);
                break;
            default:
                throw new InvalidArgumentException("Unsupported read mode {$mode}");
        }

        return $list;
    }

    /**
     * @throws InvalidArgumentException
     * @param $versioningArgs
     */
    public function validateArgs(array $versioningArgs)
    {
        $mode = $versioningArgs['mode'];

        switch ($mode) {
            case Versioned::LIVE:
            case Versioned::DRAFT:
                break;
            case 'archive':
                if (empty($versioningArgs['archiveDate'])) {
                    throw new InvalidArgumentException(sprintf(
                        'You must provide an ArchiveDate parameter when using the "%s" mode',
                        $mode
                    ));
                }
                $date = $versioningArgs['archiveDate'];
                if (!$this->isValidDate($date)) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid date: "%s". Must be YYYY-MM-DD format',
                        $date
                    ));
                }
                break;
            case 'all_versions':
                break;
            case 'latest_versions':
                break;
            case 'status':
                if (empty($versioningArgs['status'])) {
                    throw new InvalidArgumentException(sprintf(
                        'You must provide a Status parameter when using the "%s" mode',
                        $mode
                    ));
                }
                break;
            case 'version':
                // Note: Only valid for ReadOne
                if (!isset($versioningArgs['version'])) {
                    throw new InvalidArgumentException(
                        'When using the "version" mode, you must specify a Version parameter'
                    );
                }
                break;
            default:
                throw new InvalidArgumentException("Unsupported read mode {$mode}");
        }
    }

    /**
     * Returns true if date is in proper YYYY-MM-DD format
     * @param string $date
     * @return bool
     */
    protected function isValidDate($date)
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if ($dt === false) {
            return false;
        }
        // DateTime::getLastErrors() has an undocumented difference pre PHP 8.2 for what's returned
        // if there are no errors
        // https://www.php.net/manual/en/datetime.getlasterrors.php
        $errors = $dt->getLastErrors();
        // PHP 8.2 - will return false if no errors
        if ($errors === false) {
            return true;
        }
        // PHP 8.2+ will only return an array containing a count of errors only if there are errors
        // PHP < 8.2 will always return an array containing a count of errors even if there are no errors
        return array_sum($errors) === 0;
    }
}
