<?PHP

use steampixel\Tree;

/*
    For testing you can use the following table structure:
    
    CREATE TABLE IF NOT EXISTS `tree_001` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `left` int(11) NOT NULL,
      `right` int(11) NOT NULL,
      `level` int(11) NOT NULL,
      `move` int(11) NOT NULL,
      `data_column_1` text NOT NULL,
      `data_column_2` text NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    
*/

//Include Tree
require_once('Tree.php');

//Configure new Tree
$tree = Tree::factory(Array(
    'host' => 'localhost',//database host
    'user' => 'root',//database user
    'password' => '',//database password
    'db' => 'tree_test',//database name
    'table' => 'tree_001',//nested set tree table
    'charset' => 'utf8',//database connection charset
    'id' => 'id',//id-column name
    'lft' => 'left',//left-column name
    'rgt' => 'right',//right-column name
    'lvl' => 'level',//level-column name
    'mov' => 'move',//move-column name
));

//Create a new root node
$new_root_node_id = $tree->insertNode(Array('data_column_1'=>'root','data_column_2'=>'root'));

//Inject a new node into the root node
$new_sub_node_id = $tree->insertNode(Array('data_column_1'=>'sub_node','data_column_2'=>'sub_node'),$new_root_node_id,'inject');

//Create new node left
$new_left_node_id = $tree->insertNode(Array('data_column_1'=>'left_brother','data_column_2'=>'left_brother'),$new_sub_node_id,'left');

//Create new node right
$new_right_node_id = $tree->insertNode(Array('data_column_1'=>'right_brother','data_column_2'=>'right_brother'),$new_sub_node_id,'right');

//You can move the new created nodes
//$tree->moveNodeUp($new_sub_node_id);
//$tree->moveNodeDown($new_sub_node_id);
//$tree->moveNodeLeft($new_sub_node_id);
//$tree->moveNodeRight($new_sub_node_id);

//Delete the root node but keep its children by moving them to the old nodes level
//$tree->deleteNode($new_root_node_id,'preserve');

//Delete the root node and all its children
//$tree->deleteNode($new_root_node_id,'delete');

//Example function that renders the tree
function renderSimpleTree($nodes){
    
    $current_depth = 0;
    $counter = 0;

    $html = '<ul>';
    
    foreach($nodes as $node){
        
        $node_depth = $node->level;
        $node_name = $node->data_column_1;
        $node_id = $node->id;

        if($node_depth == $current_depth){
            if($counter > 0) $html.= '</li>';
        }
        elseif($node_depth > $current_depth){
            $html.= '<ul>';
            $current_depth = $current_depth + ($node_depth - $current_depth);
        }
        elseif($node_depth < $current_depth){
            $html.= str_repeat('</li></ul>',$current_depth - $node_depth).'</li>';
            $current_depth = $current_depth - ($current_depth - $node_depth);
        }
        $html.= '<li id="'.$node_id.'"';
        $html.= $node_depth < 2 ?' class="open"':'';
        $html.= '><a href="#">'.$node_name.'</a>';
        ++$counter;
    }
    
    $html.= str_repeat('</li></ul>',$node_depth).'</li>';

    $html.= '</ul>';
    
    return $html;
    
}

//Get all nodes of the tree
$all_nodes = $tree->getAllNodes();

//Get Subtree
//$sub_nodes = $tree->getSubtree($new_sub_node_id);

//Get all ancestors of a node
//$ancestor_nodes = $tree->getAncestors($new_sub_node_id);

//Get trace nodes
//$trace_nodes = $tree->getTrace($new_sub_node_id);

//Get left brother
//$left_brother = $tree->getLeftBrother($new_sub_node_id);

//Get right brother
//$right_brother = $tree->getRightBrother($new_sub_node_id);

//render the tree
echo renderSimpleTree($all_nodes);

//Render the tree status
//This function will make a few basic analytics and returns the tree status as a string.
//You can test this function by manually writing wrong values to the tree's left and right columns.
echo $tree->status();

//If the left, right, level or move values of a tree are broken you can not use the insert and move functions without producing more errors in the tree structure.
//If the tree is broken you can reset all nodes to root and level 0. From there you can use all insert and move functions savely again.
//$tree->resetNodes();


