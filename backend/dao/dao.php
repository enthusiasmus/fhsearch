<?php
/**
 * User: Enthusiasmus
 * Date: 01.08.14
 * Time: 23:11
 */

include "../config.php";

function createHuman(Human $human)
{
    global $DSN, $DB_USER, $DB_PASS;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    //Check if ID already exists, so we have only the make a update
    //Otherwise we want to insert the new human
    $select = $adapter->prepare("SELECT id FROM people WHERE id = :id");
    $result = $select->execute(array(
        ":id" => htmlspecialchars($human->getId())
    ));
    $result = $select->fetch(PDO::FETCH_ASSOC);

    if ($result["id"]) {
        return updateHumanOverview($human);
    } else {
        return insertHuman($human);
    }
}

function insertHuman(Human $human)
{
    global $DSN, $DB_USER, $DB_PASS;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    $insert = $adapter->prepare("INSERT INTO people (prename, lastname, department, type, id) VALUES (:prename, :lastname, :department, :type, :id)");
    $result = $insert->execute(array(
        ":prename" => htmlspecialchars($human->getPrename()),
        ":lastname" => htmlspecialchars($human->getLastname()),
        ":department" => htmlspecialchars($human->getDepartment()),
        ":type" => htmlspecialchars($human->getType()),
        ":id" => htmlspecialchars($human->getId())
    ));

    if (!$result) {
        header("HTTP/1.0 500 Internal Server Error");
    } else {
        return $adapter->lastInsertId();
    }
}

function updateHumanOverview(Human $human)
{
    global $DSN, $DB_USER, $DB_PASS;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    $update = $adapter->prepare("UPDATE people SET prename = :prename, lastname = :lastname, department = :department, type = :type WHERE id = :id");
    $result = $update->execute(array(
        ":prename" => htmlspecialchars($human->getPrename()),
        ":lastname" => htmlspecialchars($human->getLastname()),
        ":department" => htmlspecialchars($human->getDepartment()),
        ":type" => htmlspecialchars($human->getType()),
        ":id" => htmlspecialchars($human->getId())
    ));

    if (!$result) {
        header("HTTP/1.0 500 Internal Server Error");
    } else {
        return $human->getId();
    }
}

function updateHumanDetails(Human $human)
{
    global $DSN, $DB_USER, $DB_PASS;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    $pictureURLFHS = htmlspecialchars($human->getPictureUrlFhs());
    $pictureURLFHS = $pictureURLFHS == "" ? null : $pictureURLFHS;

    $email = htmlspecialchars($human->getEmail());
    $email = $email == "" ? null : $email;

    $id = htmlspecialchars($human->getId());
    $id = $id == "" ? null : $id;

    $phone = htmlspecialchars($human->getPhone());
    $phone = $phone == "" ? null : $phone;

    $mobile = htmlspecialchars($human->getMobile());
    $mobile = $mobile == "" ? null : $mobile;

    $room = htmlspecialchars($human->getRoom());
    $room = $room == "" ? null : $room;

    $update = $adapter->prepare("UPDATE people SET pictureUrlFhs = :pictureUrlFhs, email = :email, mobile = :mobile, phone = :phone, room = :room WHERE id = :id");
    $result = $update->execute(array(
        ":pictureUrlFhs" => $pictureURLFHS,
        ":email" => $email,
        ":id" => $id,
        ":phone" => $phone,
        ":mobile" => $mobile,
        ":room" => $room
    ));

    if (!$result) {
        header("HTTP/1.0 500 Internal Server Error");
    } else {
        return $human->getId();
    }
}

function getPeople($data, $offset)
{
    global $DSN, $DB_USER, $DB_PASS, $LIMIT;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    $data = "+" . $data;
    $data = str_replace(" ", "* +", $data);
    $data = $data + "*";

    $select = $adapter->prepare("SELECT * FROM people WHERE MATCH (search) AGAINST (:data IN BOOLEAN MODE) LIMIT :limit OFFSET :offset");
    $select->bindParam(':data', htmlspecialchars($data, ENT_QUOTES, "UTF-8"));
    $select->bindParam(':limit', $LIMIT, PDO::PARAM_INT);
    $select->bindParam(':offset', intval(htmlspecialchars($offset)), PDO::PARAM_INT);
    $result = $select->execute();

    $selectCount = $adapter->prepare("SELECT count(*) AS count FROM people WHERE MATCH (search) AGAINST (:data IN BOOLEAN MODE)");
    $selectCount->bindParam(':data', htmlspecialchars($data, ENT_QUOTES, "UTF-8"));
    $count = $selectCount->execute();

    $count = $selectCount->fetch(PDO::FETCH_ASSOC);
    $count = $count["count"];

    if (!$result) {
        header("HTTP/1.0 500 Internal Server Error");
    } else {

        $rows = array();
        foreach ($select as $row) {
            $rows[] = array(
                "lastname" => htmlspecialchars_decode($row['lastname']),
                "prename" => htmlspecialchars_decode($row['prename']),
                "email" => $row['email'],
                "id" => $row['id'],
                "department" => htmlspecialchars_decode($row['department']),
                "phone" => $row['phone'],
                "mobile" => $row['mobile'],
                "room" => $row['room'],
                "type" => $row['type']
            );
        }

        return array(
            "people" => $rows,
            "count" => $count,
            "offset" => $offset
        );
    }
}

function getStatistic()
{
    global $DSN, $DB_USER, $DB_PASS, $LIMIT;
    $adapter = new PDO($DSN, $DB_USER, $DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")) or die(false);

    $statistic = [];
    $years = array("1999", "2000", "2001", "2002", "2003", "2004", "2005", "2006", "2007", "2008", "2009", "2010", "2011", "2012", "2013", "2014", "2015");

    foreach ($years as $year) {
        $count = $adapter->query("SELECT count(*) AS count FROM people WHERE email LIKE '%" . $year . "%'");
        $count->bindParam(':year', $year);
        $count->execute();
        $all = $count->fetch(PDO::FETCH_ASSOC);
        $statistic[$year] = $all["count"];
    }

    $count = $adapter->prepare("SELECT count(*) AS count FROM people");
    $result = $count->execute();
    $all = $count->fetch(PDO::FETCH_ASSOC);
    $statistic["all"] = $all["count"];

    $count = $adapter->prepare("SELECT count(*) AS count FROM people WHERE email IS NULL");
    $result = $count->execute();
    $all = $count->fetch(PDO::FETCH_ASSOC);
    $statistic["unknown"] = $all["count"];

    if (!$result) {
        header("HTTP/1.0 500 Internal Server Error");
    } else {
        return $statistic;
    }
}

/** Error-Analysis
 * $adapter->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
 * prepare
 * execute
 * print_r($insert->errorInfo());
 */

/**
 * Show all study groups
 * SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(`email`,'@',1), '.',-1) AS group FROM test WHERE `email` != "" AND `email` LIKE '%20%' GROUP BY SUBSTRING_INDEX(SUBSTRING_INDEX(`email`,'@',1), '.',-1)
 */
