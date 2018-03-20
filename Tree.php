<?PHP

class Tree{
  
  private $config = array();
  
  private $connection;
  
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
  
  /*
    Execute mysql query
  */
  public function query($query){
    if ($result = $this->connection->query($query)) {
      return $result;
    }
    
    if($this->connection->error){
      echo 'MySQL error: '.$this->connection->error.'<br/>Last query: '.$query.'<br/>';
      exit;
    }
    
    return false;
  }
  
  /*
    Return multiple rows from a mysql query
  */
  public function getRows($query){
    $rows = Array();
    
    if($result = $this->query($query)){
      while ($obj = $result->fetch_object()) {
        array_push($rows,$obj);
      }
    }
    return $rows;
  }
  
  /*
    Return a single row from a mysql query
  */
  public function getRow($query){
    if($result = $this->query($query)){
      while ($obj = $result->fetch_object()) {
        return $obj;
      }
    }
    return false;
  }
  
  /*
    Get the left brother of a node
  */
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
  
  /*
    Get the right brother of a node
  */
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
      // Get informations about the parent node
      $relative_node = $this->getNode($relative_id);
      if($relative_node){
        
        if($mode=='inject'){
          // Create some space for the new node
          $this->query('
              UPDATE '.$this->config['table'].'
              SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
              WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
          );
          
          // Create some space for the new node
          $this->query('
              UPDATE '.$this->config['table'].'
              SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
              WHERE `'.$this->config['rgt'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
          );
          
          $data[$this->config['lft']] = $relative_node->{$this->config['rgt']};
          $data[$this->config['rgt']] = $relative_node->{$this->config['rgt']}+1;
          $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']}+1;
          
          $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
          $insert_values = '"'.implode('","',$data).'"';
          
          // Insert new node
          $this->query('
              INSERT INTO '.$this->config['table'].'
              ('.$insert_keys.') 
              VALUES('.$insert_values.')
          ');
          
          return $this->connection->insert_id;
        }
            
        if($mode=='left'){
          
          // Create some space for the new node
          $this->query('
            UPDATE '.$this->config['table'].'
            SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
            WHERE `'.$this->config['lft'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['lft']})
          );
          
          // Create some space for the new node
          $this->query('
            UPDATE '.$this->config['table'].'
            SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
            WHERE `'.$this->config['rgt'].'` >= '.$this->connection->real_escape_string($relative_node->{$this->config['lft']})
          );

          $data[$this->config['lft']] = $relative_node->{$this->config['lft']};
          $data[$this->config['rgt']] = $relative_node->{$this->config['lft']}+1;
          $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']};
          
          $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
          $insert_values = '"'.implode('","',$data).'"';
          
          // Insert new node
          $this->query('
            INSERT INTO '.$this->config['table'].'
            ('.$insert_keys.') 
            VALUES('.$insert_values.')
          ');
          
          return $this->connection->insert_id;
        }
        
        if($mode=='right'){
            
          // Create some space for the new node
          $this->query('
            UPDATE '.$this->config['table'].'
            SET `'.$this->config['lft'].'` = `'.$this->config['lft'].'`+2 
            WHERE `'.$this->config['lft'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
          );
          
          // Create some space for the new node
          $this->query('
            UPDATE '.$this->config['table'].'
            SET `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`+2 
            WHERE `'.$this->config['rgt'].'` > '.$this->connection->real_escape_string($relative_node->{$this->config['rgt']})
          );

          $data[$this->config['lft']] = $relative_node->{$this->config['rgt']}+1;
          $data[$this->config['rgt']] = $relative_node->{$this->config['rgt']}+2;
          $data[$this->config['lvl']] = $relative_node->{$this->config['lvl']};
          
          $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
          $insert_values = '"'.implode('","',$data).'"';
          
          // Insert new node
          $this->query('
            INSERT INTO '.$this->config['table'].'
            ('.$insert_keys.') 
            VALUES('.$insert_values.')
          ');
          
          return $this->connection->insert_id;
        }
      }
      
    }else{
        
      // Create new root node
      
      // Count existing nodes
      $row = $this->getRow('
        SELECT COUNT(*) AS count FROM '.$this->config['table'].'
      ');

      $data[$this->config['lft']] = $row->count*2+1;
      $data[$this->config['rgt']] = $row->count*2+2;
      $data[$this->config['lvl']] = 0;
      
      $insert_keys = '`'.implode('`,`',array_keys($data)).'`';
      $insert_values = '"'.implode('","',$data).'"';
      
      // Insert new node
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
    
    $brother = $this->getLeftBrother($node_id);

    if($node&&$brother){
      
      // Calculate the number of nodes between the node to be moved and the brother
      $right_diffrence = $node->{$this->config['rgt']} - $brother->{$this->config['rgt']};
      $left_diffrence = $node->{$this->config['lft']} - $brother->{$this->config['lft']};
      
      // Reset move values
      $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');
      
      // Move node
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
      
      // Reset move values
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
    
    $brother = $this->getRightBrother($node_id);

    if($node&&$brother){
      
      // Calculate the number of nodes between the node to be moved and the brother
      $right_diffrence = $brother->{$this->config['rgt']} - $node->{$this->config['rgt']};
      $left_diffrence = $brother->{$this->config['lft']} - $node->{$this->config['lft']};
      
      // Reset move values
      $this->query('UPDATE '.$this->config['table'].' SET '.$this->config['mov'].' = 0');

      // Move node
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
      
      // Reset move values
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
    
    // Read out information about the parent node
    $parent = $this->getAncestor($node_id);

    if($node&&$parent){
        
      // Move the node to the right until it stops
      while(true){
        if(!$this->moveNodeRight($node_id)){
          break;
        }
      }
      
      // Reread the node because its values ​​have changed after being moved
      $node = $this->getNode($node_id);
      
      $node_width = $node->{$this->config['rgt']}-$node->{$this->config['lft']}+1;
      
      // Update all nodes between left and right
      $this->query('
        UPDATE '.$this->config['table'].'
        SET 
          `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'` + 1, 
          `'.$this->config['lft'].'` = `'.$this->config['lft'].'` + 1,
          `'.$this->config['lvl'].'` = `'.$this->config['lvl'].'` - 1
        WHERE 
          `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']})
      );
      
      // Update parent
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
      
      // Update parent
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
    
    // For each node
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
        
        // Move child nodes up one level
        $this->query('
          UPDATE '.$this->config['table'].'
          SET
            `'.$this->config['lft'].'` = `'.$this->config['lft'].'`-1,
            `'.$this->config['rgt'].'` = `'.$this->config['rgt'].'`-1,
            `'.$this->config['lvl'].'` = `'.$this->config['lvl'].'`-1
          WHERE `'.$this->config['lft'].'` BETWEEN '.$this->connection->real_escape_string($node->{$this->config['lft']}).' AND '.$this->connection->real_escape_string($node->{$this->config['rgt']})
        );
        
        // Manipulate the right neighbor tree and close the hole in the nested set
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
        
        // Get all the items to be deleted
        $nodes = $this->getSubtree($node_id);
        
        foreach($nodes as $node){
          $this->query('
            DELETE 
            FROM '.$this->config['table'].'
            WHERE `'.$this->config['id'].'` = '.$this->connection->real_escape_string($node->{$this->config['id']})
          );
        }

        // Manipulate the right neighbor tree and close the hole in the nested set
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
