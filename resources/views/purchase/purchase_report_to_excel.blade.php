<html>
  <body>
    <h1>Rubai</h1>
    <?php 
    echo exec('cd ~/Desktop/platform-tools; adb pull /storage/emulated/0/Pictures/CerebrumImages/222.jpg ~/Desktop/CerebrumImages/');
    //echo shell_exec('export PATH="/Users/hmrubai/Desktop/platform-tools/":$PATH; cd ~/Desktop/platform-tools; adb devices; adb pull /storage/emulated/0/Pictures/CerebrumImages/222.jpg ~/Desktop/CerebrumImages/; adb shell rm -f /storage/emulated/0/Pictures/CerebrumImages/222.jpg');

    //exec('cd C:\Users\User\Desktop\platform-tools adb pull /storage/emulated/0/Pictures/CerebrumImages/rubai.jpg C:\Users\User\Desktop\CerebrumImages');

      echo "rubai";
      echo "<pre>";
      print_r($purchase_data);
      exit;  
    ?>
    <table border="1px">
      <thead>

      </thead>
    </table>
  </body>
</html>