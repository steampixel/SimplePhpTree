<?PHP

namespace steampixel;

class Tree{
    
    private $config = array();
    
    private $connection;
    
    /*
        Tree Factory
    */
    public static function factory($config){

        return new self($config);
        
    }
    
    public function __construct($config){
        
        $this->config = $config;
        
        $this->connection = new \mysqli(
            $this->config['host'], 
            $this->config['user'], 
            $this->config['password'], 
            $this->config['db']
        );
        
        $this->connection->set_charset($this->config['charset']);

    }
    
    public function __destruct(){
        
        $this->connection->close();
        
    }
    
    public function query($query){
        
        //echo $query.'<br/>';
        
        if ($result = $this->connection->query($query)) {
            return $result;
        }
        
        if($this->connection->error){
            
            echo 'MySQL Error: '.$this->connection->error.'<br/>Last Query: '.$query.'<br/>';
            exit;
            
        }
        
        return false;
        
    }
    
    public function getRows($query){
        
        $rows = Array();
        
        if($result = $this->query($query)){
            
            while ($obj = $result->fetch_object()) {
                array_push($rows,$obj);
            }
            
        }
        
        return $rows;
    }
    
    public function getRow($query){
        if($result = $this->query($query)){
            while ($obj = $result->fetch_object()) {
                return $obj;//Return first result
            }
        }
        return false;
    }
    
    public function getLeftBrother($node_id){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRow('
                SELECT *
                FROM '.$this->config['table'].'
                WHERE `'.$this->config['rgt'].'` = '.$this->connection->real_escape_string($node->{$this->config['lft']}-1).'
            ');
        
        }
        
        return false;
        
    }
    
    public function getRightBrother($node_id){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRow('
                SELECT *
                FROM '.$this->config['table'].'
                WHERE `'.$this->config['lft'].'` = '.$this->connection->real_escape_string($node->{$this->config['rgt']}+1).'
            ');
        
        }
        
        return false;
        
    }
    
    /*
        Return all Ancestor Nodes
    */
    public function getAncestors($node_id){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRows('
                SELECT *
                FROM '.$this->config['table'].' child, '.$this->config['table'].' ancestor 
                WHERE child.`'.$this->config['lft'].'` > ancestor.`'.$this->config['lft'].'` AND child.`'.$this->config['lft'].'` < ancestor.`'.$this->config['rgt'].'` 
                AND child.`'.$this->config['id'].'` = '.$this->connection->real_escape_string($node->{$this->config['id']}).' 
                ORDER BY ancestor.`'.$this->config['lft'].'`
            ');
        }
        
        return false;
    }
    
    /*
        Return all ancestor Nodes including the child node
    */
    public function getTrace($node_id){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRows('
                SELECT *
                FROM '.$this->config['table'].' child, '.$this->config['table'].' ancestor 
                WHERE child.`'.$this->config['lft'].'` >= ancestor.`'.$this->config['lft'].'` AND child.`'.$this->config['lft'].'` <= ancestor.`'.$this->config['rgt'].'` 
                AND child.`'.$this->config['id'].'` = '.$this->connection->real_escape_string($node->{$this->config['id']}).' 
                ORDER BY ancestor.`'.$this->config['lft'].'`
            ');
        
        }
        
        return false;
        
    }
    
    /*
        Get the first ancestor
    */
    public function getAncestor($node_id){

        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRow('
                SELECT *
                FROM '.$this->config['table'].' child, '.$this->config['table'].' ancestor 
                WHERE child.`'.$this->config['lft'].'` > ancestor.`'.$this->config['lft'].'` AND child.`'.$this->config['lft'].'` < ancestor.`'.$this->config['rgt'].'` 
                AND child.`'.$this->config['id'].'` = '.$this->connection->real_escape_string($node->{$this->config['id']}).' 
                ORDER BY ancestor.`'.$this->config['lft'].'` DESC
                LIMIT 1
            ');
        
        }
        
        return false;
        
    }
    
    public function getNode($node_id){
        
        return $this->getRow('
            SELECT *
            FROM '.$this->config['table'].'
            WHERE `'.$this->config['id'].'` = '.$this->connection->real_escape_string($node_id).'
        ');
        
    }
    
    /*
        Returns all subree nodes including the parent node
    */
    public function getSubtree($node_id){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            return $this->getRows('
                SELECT *
                FROM '.$this->config['table'].'
                WHERE `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']})
            );
        
        }
        
        return false;
        
    }
    
    /*
        Return all tree items in corrent order
    */
    public function getAllNodes(){
        
        return $this->getRows('
            SELECT * 
            FROM '.$this->config['table'].'
            ORDER BY `'.$this->config['lft'].'` ASC
        ');
        
    }
        
    /*
        Insert a node relative to another node
    */
    public function insertNode($data=Array(),$relative_id=0,$mode='inject'){

        if($relative_id){
        
            //Informationen über den Parent bekommen
            $relative_node = $this->getNode($relative_id);
            
            if($relative_node){
            
                if($mode=='inject'){
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
                        WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
                    );
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
                        WHERE `'.$this->config['rgt'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
                    );
                    
                    //Daten aufbauen
                    $data[$this->config['lft']] = $relative_node->{$this->config['rgt']};
                    $data[$this->config['rgt']] = $relative_node->{$this->config['rgt']}+1;
                    $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']}+1;
                    
                    $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
                    $insert_values = '"'.implode('","',$data).'"';
                    
                    //Neuen Knoten einfügen
                    $this->query('
                        INSERT INTO '.$this->config['table'].'
                        ('.$insert_keys.') 
                        VALUES('.$insert_values.')
                    ');
                    
                    return $this->connection->insert_id;
                    
                }
                
                if($mode=='left'){
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
                        WHERE `'.$this->config['lft'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['lft']})
                    );
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
                        WHERE `'.$this->config['rgt'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['lft']})
                    );
                    
                    //Daten aufbauen
                    $data[$this->config['lft']] = $relative_node->{$this->config['lft']};
                    $data[$this->config['rgt']] = $relative_node->{$this->config['lft']}+1;
                    $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']};
                    
                    $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
                    $insert_values = '"'.implode('","',$data).'"';
                    
                    //Neuen Knoten einfügen
                    $this->query('
                        INSERT INTO '.$this->config['table'].'
                        ('.$insert_keys.') 
                        VALUES('.$insert_values.')
                    ');
                    
                    return $this->connection->insert_id;
                    
                }
                
                if($mode=='right'){
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
                        WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
                    );
                    
                    //Platz für den neuen Knoten schaffen
                    $this->query('
                        UPDATE '.$this->config['table'].'
                        SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
                        WHERE `'.$this->config['rgt'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
                    );
                    
                    //Daten aufbauen
                    $data[$this->config['lft']] = $relative_node->{$this->config['rgt']}+1;
                    $data[$this->config['rgt']] = $relative_node->{$this->config['rgt']}+2;
                    $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']};
                    
                    $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
                    $insert_values = '"'.implode('","',$data).'"';
                    
                    //Neuen Knoten einfügen
                    $this->query('
                        INSERT INTO '.$this->config['table'].'
                        ('.$insert_keys.') 
                        VALUES('.$insert_values.')
                    ');
                    
                    return $this->connection->insert_id;
                    
                }
            
            }
            
        }else{
            
            //Create new root node
            
            //Count existing nodes
            $row = $this->getRow('
                SELECT COUNT(*) AS count FROM '.$this->config['table'].'
            ');

            //Daten aufbauen
            $data[$this->config['lft']] = $row->count*2+1;
            $data[$this->config['rgt']] = $row->count*2+2;
            $data[$this->config['lvl']] = 0;
            
            $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
            $insert_values = '"'.implode('","',$data).'"';
            
            //Neuen Knoten einfügen
            $this->query('
                INSERT INTO '.$this->config['table'].'
                ('.$insert_keys.') 
                VALUES('.$insert_values.')
            ');
            
            return $this->connection->insert_id;
            
        }
        return false;
        
    }
    
    /*
        Move a node left
    */
    public function moveNodeLeft($node_id){
        
        $node = $this->getNode($node_id);
        
        //Daten über den Bruderknoten bekommen
        $brother = $this->getLeftBrother($node_id);

        if($node&&$brother){
            
            //Anzahl der Knoten zwischen dem zu verschiebenden Node und dem Bruder berechnen
            $right_diffrence = $node->{$this->config['rgt']} - $brother->{$this->config['rgt']};
            $left_diffrence = $node->{$this->config['lft']} - $brother->{$this->config['lft']};
            
            //Move Werte zurücksetzen
            $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');
            
            //Knoten verschieben
            $this->query('
                UPDATE '.$this->config['table'].'
                SET
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` + '.$this->connection->real_escape_string($right_diffrence).', 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` + '.$this->connection->real_escape_string($right_diffrence).', 
                    `'.$this->config['mov'].'` = 1 
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($brother->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($brother->{$this->config['rgt']})
            );
          
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` - '.$this->connection->real_escape_string($left_diffrence).', 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` - '.$this->connection->real_escape_string($left_diffrence).'
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']}).'
                    AND `'.$this->config['mov'].'` = 0'
            );
            
            //Move Werte zurücksetzen
            $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');
            
            return true;
            
        }
        
        return false;
        
    }
    
    /*
        Move a node to the right
    */
    public function moveNodeRight($node_id){
        
        $node = $this->getNode($node_id);
        
        //Daten über den Bruderknoten bekommen
        $brother = $this->getRightBrother($node_id);

        if($node&&$brother){
            
            //Anzahl der Knoten zwischen dem zu verschiebenden Node und dem Bruder berechnen
            $right_diffrence = $brother->{$this->config['rgt']} - $node->{$this->config['rgt']};
            $left_diffrence = $brother->{$this->config['lft']} - $node->{$this->config['lft']};
            
            //Move Werte zurücksetzen
            $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');

            //Knoten verschieben
            $this->query('
                UPDATE '.$this->config['table'].'
                SET
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` - '.$this->connection->real_escape_string($left_diffrence).', 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` - '.$this->connection->real_escape_string($left_diffrence).', 
                    `'.$this->config['mov'].'` = 1 
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($brother->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($brother->{$this->config['rgt']})
            );
          
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` + '.$this->connection->real_escape_string($right_diffrence).', 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` + '.$this->connection->real_escape_string($right_diffrence).'
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']}).'
                    AND `'.$this->config['mov'].'` = 0'
            );
            
            //Move Werte zurücksetzen
            $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');
            
            return true;
            
        }
        
        return false;
        
    }
    
    /*
        Move node up
    */
    public function moveNodeUp($node_id){
        
        $node = $this->getNode($node_id);
        
        //Informationen über den Parent-Knoten auslesen
        $parent = $this->getAncestor($node_id);

        if($node&&$parent){
            
            //Den Knoten so lange nach rechts verschieben, bis es nicht mehr geht
            while(true){

                if(!$this->moveNodeRight($node_id)){
                   break;
                }
                
            }
            
            $node = $this->getNode($node_id);//Node neu auslesen, da sich seine Werte nach dem Verschieben verändert haben
            
            $node_width = $node->{$this->config['rgt']}-$node->{$this->config['lft']}+1;
            
            //Alle Knoten zwischen left und right aktualisieren
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` + 1, 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` + 1,
                    `'.$this->config['lvl'].'` = `'.$this->config['lvl'].'` - 1
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']})
            );
            
            
            //Parent aktualisieren
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` - '.$this->connection->real_escape_string($node_width).'
                WHERE 
                    `'.$this->config['id'].'` = '.$this->connection->real_escape_string($parent->{$this->config['id']})
            );
            
            
            return true;
            
        }
        
        return false;
        
    }
    
    /*
        Move node down
    */
    public function moveNodeDown($node_id){
        
        $node = $this->getNode($node_id);
        
        //Den linken bruder bekommen
        //Der wird der neue Parent
        $brother = $this->getLeftBrother($node_id);

        if($node&&$brother){

            $node_width = $node->{$this->config['rgt']}-$node->{$this->config['lft']}+1;
            
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` - 1, 
                    `'.$this->config['lft'].'` = `'.$this->config['lft'].'` - 1,
                    `'.$this->config['lvl'].'` = `'.$this->config['lvl'].'` + 1
                WHERE 
                    `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']}).';
            ');
            
            //Parent aktualisieren
            $this->query('
                UPDATE '.$this->config['table'].'
                SET 
                    `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` + '.$this->connection->real_escape_string($node_width).'
                WHERE 
                    `'.$this->config['id'].'` = '.$this->connection->real_escape_string($brother->{$this->config['id']})
            );
            
            return true;
            
        }
        
        return false;
        
    }
    
    /*
        Check status of nested set system
    */
    public function status(){
        
        $nodes = $this->getAllNodes();
        
        $left_rgt_values = Array();
        
        $error_string = '';
        
        //For each node
        foreach($nodes as $node){
                        
            if($node->{$this->config['lft']}==0){
                $error_string.= 'The nested set seems to be broken. This node left value is zero: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }
            
            if($node->{$this->config['rgt']}==0){
                $error_string.= 'The nested set seems to be broken. This node right value is zero: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }
            
            if($node->{$this->config['lft']}<0){
                $error_string.= 'The nested set seems to be broken. This node left value is smaler than zero: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }
            
            if($node->{$this->config['rgt']}<0){
                $error_string.= 'The nested set seems to be broken. This node right value is smaler than zero: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }
            
            if(array_key_exists($node->{$this->config['lft']},$left_rgt_values)){
                $error_string.= 'The nested set seems to be broken. This node left value was already found: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }else{
                $left_rgt_values[$node->{$this->config['lft']}] = true;
            }
            
            if(array_key_exists($node->{$this->config['rgt']},$left_rgt_values)){
                $error_string.= 'The nested set seems to be broken. This node right value was already found: ';
                $error_string.= '<pre>'.print_r($node,true).'</pre>';
            }else{
                $left_rgt_values[$node->{$this->config['rgt']}] = true;
            }
            
        }
        
        if(count($left_rgt_values)!=count($nodes)*2){
            
            $error_string.= 'The nested set seems to be broken. There is a mismatch between the node count and the count of the left and right values: ';
            $error_string.= '<pre>'.print_r($left_rgt_values,true).'</pre>';            
            
        }
        
        if($error_string!=''){
            return $error_string;
        }
        
        return 'ok';
        
    }
    
    /*
        Reset all nodes to a clean nested set
    */
    public function resetNodes(){
        
        $nodes = $this->getRows('
            SELECT * 
            FROM '.$this->config['table'].'
            ORDER BY `'.$this->config['id'].'` ASC
        ');
        
        $lft = 1;
        $rgt = 2;
        
        foreach($nodes as $node){
            
            $this->query('
                UPDATE '.$this->config['table'].'
                SET
                    `'.$this->config['lft'].'` = '.$lft.',
                    `'.$this->config['rgt'].'` = '.$rgt.',
                    `'.$this->config['lvl'].'` = 0,
                    `'.$this->config['mov'].'` = 0
                WHERE `'.$this->config['id'].'` = '.$node->id.'
            ');
            
            $lft+=2;
            $rgt+=2;
            
        }
        
    }
    
    /*
        Delet nodes
    */
    public function deleteNode($node_id,$children_mode='preserve'){
        
        $node = $this->getNode($node_id);
        
        if($node){
        
            if($children_mode=='preserve'){
                
                $this->query('
                    DELETE FROM '.$this->config['table'].'
                    WHERE '.$this->config['id'].' = '.$this->connection->real_escape_string($node->{$this->config['id']})
                );
                
                //Kindknoten ein Level nach oben schieben
                $this->query('
                    UPDATE '.$this->config['table'].'
                    SET
                        `'.$this->config['lft'].'` = `'.$this->config['lft'].'`-1,
                        `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`-1,
                        `'.$this->config['lvl'].'` = `'.$this->config['lvl'].'`-1
                    WHERE `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']})
                );
                
                //Rechten Nachbarbaum manipulieren und das Loch im Nested Set schließen
                $this->query('
                    UPDATE '.$this->config['table'].'
                    SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`-2 
                    WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($node->{$this->config['rgt']})
                );
                
                $this->query('
                    UPDATE '.$this->config['table'].'
                    SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`-2 
                    WHERE `'.$this->config['rgt'].'` > '.$this->connection->real_escape_string($node->{$this->config['rgt']})
                );
                
                return true;
                
            }
            
            if($children_mode=='delete'){
                
                //Alle zu löschenden Elemente bekommen
                $nodes = $this->getSubtree($node_id);
                
                foreach($nodes as $node){

                    $this->query('
                        DELETE 
                        FROM '.$this->config['table'].'
                        WHERE `'.$this->config['id'].'` = '.$this->connection->real_escape_string($node->{$this->config['id']})
                    );

                }

                //Den rechten Nachbarbaum manipulieren und das Loch im Nested Set schließen
                $this->query('
                    UPDATE '.$this->config['table'].'
                    SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`-2*'.$this->connection->real_escape_string(count($nodes)).' 
                    WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($node->{$this->config['rgt']})
                );
                
                $this->query('
                    UPDATE '.$this->config['table'].'
                    SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`-2*'.$this->connection->real_escape_string(count($nodes)).' 
                    WHERE `'.$this->config['rgt'].'` > '.$this->connection->real_escape_string($node->{$this->config['rgt']})
                );

            }
        
        }
        
        return false;
        
    }

    
}