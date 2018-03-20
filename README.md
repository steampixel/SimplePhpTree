# Simple PHP Tree - Nested sets for PHP and MySQL
This a simple PHP tree class for managing multi-root nested set tables. 
You can create new nodes in the tree, move them left, right, up and down. 
You can also delete nodes and preserve its children or you can delete complete subtrees. 

There is a build in function that shows the health status of the tree table.
If the tree gets broken for some reason you can simply reset the tree by calling the ```resetNodes()``` method. 

Take a look at the index.php file for a full feature example. 
There you will also find a example function that renders a simple multi-level ul-list.

## Table structure for testing
For testing purposes you can use the following table structure:
```
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
```

## MIT License

Copyright (c) 2018 SteamPixel

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.