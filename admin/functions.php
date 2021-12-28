<?php

// Admin Functions ////////////////////////////////////////////////////

function delete_record($table = null, $idName = null, $id = null)
{
    $where = "{$idName} = {$id} ";
    if (e107::getDb()->count($table, '(*)', $where)) {
        // delete record
        if (e107::getDb()->delete($table, $where)) {
            return true;
        } else {
            return false;
        }
    }
    
    return false;
}
