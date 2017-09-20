<?php
namespace Craft;

class FB2Export_ExportController extends BaseController
{
    protected $allowAnonymous = false;

    public function actionExport() {
        $request = craft()->request;
        $auth = $request->getParam('auth');
        $form = $request->getParam('formId');
        $format = strtolower($request->getParam('format'));
        $limit = $request->getParam('limit');
        $columns = array();
        $rows = array();
        $content = '';

        // if ($auth !== 'FKFtREpuuuj9FAvTwsbnfchb') {
        //     $this->requireToken();
        //     exit;
        // }

        $query = craft()->db->createCommand();

        $query->from('formbuilder2_entries');

        $where = array();

        if (!empty($form)) {
            array_push($where, '`formId`=' . $form);
        }

        if (!empty($limit)) {
            array_push($where, '`dateUpdated`>="' . $limit . '"');
        }

        $query->where(implode($where, ' and '));
        $results = $query->queryAll();

        foreach ($results as $index => $result) {
            $results[$index]['submission'] = json_decode($result['submission']);
        }
        
        if (empty($format) || $format == 'json') {
            return $this->returnJson($results);
        }

        foreach ($results as $index => $result) {
            if (is_string($result['submission'])) {
                $result['submission'] = json_decode($result['submission']);
            }

            foreach ($result as $key => $value) {
                if ($key == 'submission') continue;

                if (!empty($value) && $value != 'on') {
                    array_push($columns, $key);
                }
            }

            foreach ($result['submission'] as $key => $value) {
                if (!empty($value) && $value != 'on') {
                    array_push($columns, $key);
                }
            }
        }

        $columns = array_unique($columns);

        usort($columns, function ($a, $b) {
            $colSort = [
                'id', 'formId', 'title', 'files',

                'dateCreated', 'dateUpdated', 'uid',

                // other fields here
            ];

            $pos1 = array_search($a, $colSort);
            $pos2 = array_search($b, $colSort);

            if ($pos1 === false && $pos2 === false) {
                return strcmp($a, $b);
            }

            if ($pos1 === false) {
                $pos1 = sizeof($colSort);
            }
            
            if ($pos2 === false) {
                $pos2 = sizeof($colSort);
            }

            $ret = $pos1 - $pos2;

            if ($ret > 0) {
                return 1;
            } else if ($ret < 0) {
                return -1;
            }

            return 0;
        });

        foreach ($results as $index => $result) {
            if (is_string($result['submission'])) {
                $result['submission'] = json_decode($result['submission']);
            }

            $row = array();
            foreach ($columns as $column) {
                if ($column == 'files' && array_key_exists('files', $result)) {
                    $files = json_decode($result['files']);

                    if (sizeof($files) < 1) {
                        array_push($row, null);
                        continue;
                    }

                    $fq = craft()->db->createCommand();
                    $fq->from('assetfiles');
                    $fqWhere = array();

                    foreach ($files as $fileId) {
                        if (!empty($fileId)) {
                            array_push($fqWhere, 'id=' . $fileId);
                        }
                    }

                    $filesArray = array();

                    if (sizeof($fqWhere) > 0) {
                        $fq->where(implode($fqWhere, ' or '));

                        $files = $fq->queryAll();

                        foreach ($files as $file) {
                            array_push($filesArray, 'http://' . $_SERVER['HTTP_HOST'] . '/assets/site/' . urlencode($file['filename']));
                        }
                    }

                    array_push($row, '"' . implode($filesArray, " ") . '"');

                    continue;
                }

                if (array_key_exists($column, $result)) {
                    $value = json_encode($result[$column]);
                } else if (property_exists($result['submission'], $column)) {
                    $object = $result['submission']->{$column};

                    if (is_object($object) && count((array)$object) == 1) {
                        $value = str_replace('\/', '/', json_encode($object->{array_keys((array)$object)[0]}));
                    } else if (is_array($object)) {
                        $value = json_encode(implode(', ', $object));
                    } else {
                        $value = json_encode($object);
                    }
                }

                if (sizeof($value) > 0 && substr($value, 0, 1) !== '"') {
                    $value = json_encode($value);
                }

                $value = preg_replace('/\n+/', ' ', $value);

                array_push($row, $value);

                $value = '';
            }

            array_push($rows, implode(',', $row));
        }

        $content .= implode($columns, ', ') . "\n";
        $content .= implode($rows, "\n");

        // echo $content;

        craft()->request->sendFile($form . '-export.csv', $content, array(
            'mimeType' => 'text/csv',
            'forceDownload' => true
            ), true);

        craft()->log->removeRoute('WebLogRoute');
        craft()->log->removeRoute('ProfileLogRoute');
    }
}
?>