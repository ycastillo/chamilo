<?php
require_once(api_get_path(LIBRARY_PATH).'geometry.lib.php');

class TestGeometry extends UnitTestCase {

	public function TestGeometry(){

		$this->UnitTestCase('calculate and return the area of an irregular polygon');

	}

	public function testCalculateArea(){
		$xs = 12;
		$ys = 12;
		$res = calculate_area($xs,$ys);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testSubCalculation(){
		$a='88';
		$b='23';
		$res = subCalculation($a,$b);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testLinesIntersect(){
		$line1 = '2';
		$line2 = '4';
		$res = lines_intersect($line1,$line2);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testIsInSegment(){
		$p= 16;
		$a= 22;
		$b= 11;
		$res = _is_in_segment($p, $a, $b);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testIsInLine(){
		$p=221;
		$a=11;
		$b=13;
		$res = _is_in_line($p, $a, $b);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetSlope(){
		$p=33;
		$q=23;
		$res = _get_slope($p, $q);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res === 0);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testCheckPolygon(){
		$xs=12;
		$ys=12;
		$res = _check_polygon($xs, $ys);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testPolygon2String(){
		$xs=12;
		$ys=12;
		$res = polygon2string($xs, $ys);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testIsInsidePolygon(){
		$p=43;
		$xs=12;
		$ys=12;
		$res =  _is_inside_polygon($p, $xs, $ys);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testGetInsideConditionals(){
		$xs=array(12);
		$ys=array(12);
		$res = _get_inside_conditionals($xs, $ys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetIntersectionData(){
		$rxs=56;
		$rys=11;
		$uxs=3;
		$uys=12;
		$res = get_intersection_data($rxs, $rys, $uxs, $uys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetIntersectionPolygon(){
		$rxs= 11;
		$rys= 23;
		$uxs= 54;
		$uys= 56;
		$res = get_intersection_polygon($rxs, $rys, $uxs, $uys);
		$this->assertTrue(is_null($res));
		$this->assertTrue($res === null);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetIntersectionPolygonData(){
		$rxs=34;
		$rys=22;
		$uxs=44;
		$uys=13;
		$res = _get_intersection_polygon_data($rxs, $rys, $uxs, $uys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testFUllyInside(){
		$axs=12;
		$ays=33;
		$bxs=14;
		$bys=54;
		$res = fully_inside($axs, $ays, $bxs, $bys);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testIsNextTo(){
		$point = 32;
		$last_point = 10;
		$xs = 12;
		$ys = 12;
		$between_points = FALSE;
		$res = _is_next_to($point, $last_point, $xs, $ys, $between_points);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetRightPoint(){
		$point=44;
		$polygon= array(12,12);
		$res = _get_right_point($point, $polygon);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetLeftPoint(){
		$point = 44;
		$polygon= array(12,12);
		$res = _get_left_point($point, $polygon);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testToPointsArray(){
		$xs=12;
		$ys=12;
		$res = _to_points_array($xs, $ys);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testToPointsIntersection(){
		$xs=12;
		$ys=12;
		$res = _to_points_intersection($xs, $ys);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testInInternArray(){
		$point = 34;
		$intern_points = array();
		$res = in_intern_array($point,$intern_points);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}
/*	
	public function TestDeleteCourse(){				
		$code = 'COURSEX';				
		$res = CourseManager::delete_course($code);			
		$path = api_get_path(SYS_PATH).'archive';		
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {				
				if (strpos($file,$code)!==false) {										
					if (is_dir($path.'/'.$file)) {						
						rmdirr($path.'/'.$file);						
					}				
				}				
			}
			closedir($handle);
		}
	}	
	*/
}
?>
