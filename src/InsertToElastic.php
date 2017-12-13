<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 9.12.2017
 * Time: 19:13
 */

namespace src;

use Elastica\Client;
use Elastica\Document;
use Exception;

class InsertToElastic
{
    private $elasticaIndex;

    private $elasticaDealType;

    private $elasticaUserType;

    private $elasticaClient;

    private $elasticaItemDetailsData;

    public function __construct($index = null, $type1 = null, $type2 = null)
    {
        $this->elasticaClient = new Client();

        if ($index != null && $type1 != null && $type2 != null) {
            $this->elasticaIndex = $index;
            $this->elasticaDealType = $type1;
            $this->elasticaUserType = $type2;
            $this->elasticaDealItems = $this->csv_to_associative_array($_SERVER['PWD'] . '/CSVFiles/train_dealitems.csv');
            $this->elasticaItemDetailsData = $this->csv_to_associative_array($_SERVER['PWD']. '/CSVFiles/train_deal_details.csv');
        } else {
            throw new Exception("variable index or type was not set");
        }
    }

    public function insert() {
        //$this->insertDeals();
        $this->insertUsers();
    }

    private function csv_to_associative_array($file, $delimiter = ',', $enclosure = '"')
    {
        if (($handle = fopen($file, "r")) !== false) {
            $headers = fgetcsv($handle, 0, $delimiter, $enclosure);
            // clean header
            for($i = 0; $i < count($headers); $i++) {
                $headers[$i] = preg_replace('/\s+/', '', $headers[$i]);
            }

            $lines = [];
            while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
                $current = [];
                $i = 0;
                foreach ($headers as $header) {
                    $current[$header] = $data[$i++];
                }
                $lines[] = $current;
            }
            fclose($handle);
            return $lines;
        }
    }

    public function prepareDealContent($item) {

        // check if title is set
        $space = [];
        $title = $item['title_deal'];
        preg_match('/\w{2,}\s/', $item['title_deal'], $space);
        if (($item['title_deal'] != NULL) && (count($space) != 0)) {

            // set end time data from item dealitems CSV

            $i = 0;
            while ((preg_replace('/\s+/', '', $this->elasticaDealItems[$i]['deal_id']) != preg_replace('/\s+/', '', $item['id'])) && ($i < count($this->elasticaDealItems))) {
                $i++;
            }

            if (($i < count($this->elasticaDealItems)) && (is_numeric($this->elasticaDealItems[$i]['coupon_end_time']))) {
                $item['coupon_end_time'] = $this->elasticaDealItems[$i]['coupon_end_time'];
            }
            else {
                $item['coupon_end_time'] = 1;
            }

            return $item;
        }

        return NULL;
    }

    public function insertDeals() {

        $item= 0;
        $dataCounts = count($this->elasticaItemDetailsData);
        $elasticaType  = $this->elasticaClient->getIndex($this->elasticaIndex)->getType($this->elasticaDealType);
        $documents = array();

        while ($item < $dataCounts) {
            $to = $dataCounts - $item - 1000;
            ($to < 1000) ? ($to+= $item + 1000) : ($to = $item + 1000);

            for (; $item < $to; $item++) {
                $preparedItem = $this->prepareDealContent($this->elasticaItemDetailsData[$item]);
                if (isset($preparedItem)) {
                    //clean item details data
                    $this->elasticaItemDetailsData[$item]['id'] = preg_replace('/\s+$/', '', $preparedItem['id']) + 0;
                    $this->elasticaItemDetailsData[$item]['coupon_end_time'] = preg_replace('/\s+$/', '', $preparedItem['coupon_end_time']) + 0;
                    $this->elasticaItemDetailsData[$item]['partner_id'] = preg_replace('/\s+$/', '', $preparedItem['partner_id']) + 0;
                    $this->elasticaItemDetailsData[$item]['title_deal'] = preg_replace('/\s+$/', '', $preparedItem['title_deal']);
                    $this->elasticaItemDetailsData[$item]['deal_id'] = preg_replace('/\s+$/', '', $preparedItem['deal_id']) + 0;
                    $documents[] = new Document($this->elasticaItemDetailsData[$item]['id'], [
                        'deal_id'           => $this->elasticaItemDetailsData[$item]['id'],
                        'title'             => $this->elasticaItemDetailsData[$item]['title_deal'],
                        'coupon_end_time'   => $this->elasticaItemDetailsData[$item]['coupon_end_time'],
                        'partner_id'        => $this->elasticaItemDetailsData[$item]['partner_id']
                    ]);
                }
                else {
                    unset($this->elasticaItemDetailsData[$item]);
                }
            }

            $elasticaType->addDocuments($documents);

            // Refresh Index
            $elasticaType->getIndex()->refresh();
            $item++;
        }

        unset($this->elasticaDealItems);
    }

    public function getUiqueUser($data) {
        $uniqueUsers = [];
        foreach ($data as $user) {
            $id = preg_replace('/\s+$/', '', $user['user_id']) + 0;
            $deal_id = preg_replace('/\s+$/', '', $user['deal_id']) + 0;
            $uniqueUsers[$id]['deal_id'][$deal_id] = $deal_id ;
            $uniqueUsers[$id]['id'] = $id;

            /*if (count($uniqueUsers[$id]) > 2) {
                $idd = $id;
                $b = $uniqueUsers[$id];
                $aa = 0;
            }*/
        }

        return $uniqueUsers;

    }

    public function setUsers($users) {
        $titles = '';
        $setUser = [];
        $elasticaType  = $this->elasticaClient->getIndex($this->elasticaIndex)->getType($this->elasticaUserType);
        $documents = array();
        //$user_ids = array_keys($user);
        $a = 1;
        foreach ($users as $user) {
            $titles = '';
            foreach ($user['deal_id'] as $deal_id) {
                $i = 0;

                foreach ($this->elasticaItemDetailsData as $deal) {
                    if ((preg_replace('/\s+$/', '', $deal['deal_id']) + 0) === $deal_id) {
                        $title = preg_replace('/\s+$/', '', $this->elasticaItemDetailsData[$i]['title_deal']);
                        $titles = $titles . preg_replace('/\s+/', '_', $title) . ' ';
                        break;
                    }
                }
                /*$space = [];
                preg_match('/\s/', $titles, $space);
                if (count($space) > 0) {
                    $a = 1;
                    $newTitles = substr($titles, 0, -1);
                    $b = 1;
                }*/
            }

            $documents[] = new Document($user['id'], [
                'user_id'       => $user['id'],
                'deal_title'    => substr($titles, 0, -1),
                'deal_count'    => count($user['deal_id'])
            ]);

            if (($a % 1000) == 0) {
                $elasticaType->addDocuments($documents);

                // Refresh Index
                $elasticaType->getIndex()->refresh();
                $documents = [];
                $a = 0;
                print_r("sent\n");
            }

            $a++;
            /*$setUser[] = [
                'user_id'       => $user['id'],
                'deal_title'    => substr($titles, 0, -1),
                'deal_count'    => count($user['deal_id'])
            ];*/

        }

        return $setUser;
    }
    public function prepareUsers($data) {
        $uniqueUsers = $this->getUiqueUser($data);
        $preparedUsers = $this->setUsers($uniqueUsers);
        return $preparedUsers;
    }

    public function insertUsers() {
        $userData = $this->csv_to_associative_array($_SERVER['PWD']. '/CSVFiles/train_activity_v2.csv');
        $preparedUsers = $this->prepareUsers($userData);

    }

}