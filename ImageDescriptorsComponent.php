<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * ImageDescriptors component
 */
class ImageDescriptorsComponent extends Component
{
    protected $_defaultConfig = [];
    protected $_image = null;
    protected $_width = null; 
    protected $_height = null;
    protected $_matrix = null;
    
    

    protected function _loadImage($image_url=null)
    {
    	$this->_image = imagecreatefromjpeg($image_url);
        $this->_width = imagesx($this->_image);
        $this->_height = imagesy($this->_image);
    }

    protected function _getRGB($x,$y)
    {
        $rgb = imagecolorat($this->_image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return [$r,$g,$b];
    }

    protected function _image2Matrix()
    {
        for ($i=0; $i < $this->_width ; $i++) { 
            for ($j=0; $j < $this->_height; $j++) { 
                $this->_matrix[$i][$j] = $this->_getRGB($i,$j);
            }
        }
    }

    protected function _partitioning()
    {
        $new_image = imagecreatetruecolor(8,8);
        imagecopyresampled($new_image,$this->_image,0,0,0,0,8,8,$this->_width,$this->_height);
        $this->_image = $new_image;
        $this->_width = 8;
        $this->_height = 8;
    }

    protected function _zigzag()
    {
        $zigzag = [1,2,9,17,10,3,4,11,18,25,33,26,19,12,5,6,13,29,27,34,41,49,42,35,28,21,14,7,8,15,22,29,36,43,50,57,58,51,44,37,30,23,16,24,31,38,45,52,59,60,53,46,39,32,49,47,54,61,62,55,48,56,63,64];
        foreach ($zigzag as $key => $value) {
            
        }
    }
    
    public function CLD($image_url=null)
    {
    	if ($image_url) {
    		$this->image = $this->_loadImage($image_url);
    	}
        $this->_partitioning();
        $this->_image2Matrix();
    }
}
