<?php
class Speedtest {
  public static function menu() {
echo '<li><a href="plugin/p='.htmlspecialchars(get_class()).'">'.htmlspecialchars(get_class()).'</a></li>';
  }

}
?>