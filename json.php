<?

//� header() ������ ������ ������ JSON-������, ���������, ��� �������� �������� JavaScript'�� � ��������� UTF-8.
header('Content-Type: application/x-javascript; charset=utf8');

// ���������� ���� � ������������ ��������
include ("huffman.php");

if(isset($_FILES['files'])) {
    $myfile = $_FILES["files"]["tmp_name"];
    $myfile_name = $_FILES["files"]["name"];
    $myfile_size = $_FILES["files"]["size"];
    $myfile_type = $_FILES["files"]["type"];
}

// ���������� ����� ������, ��� ������������ � �� ������������ ��������� �����. ����� ��� ������.
$newname = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_compr = "upload_file/" . date("Y-m-d") . "_" . rand(0, 10000) . ".txt";
$newname_zip = "upload_zip/" . date("Y-m-d") . "_" . rand(0, 10000) . ".zip";

if(!empty($myfile)) {

    if($myfile_type != "text/plain") {
        echo ("������ �������� ��� �����. ����� ��������� ������ ����� .txt");
    } else {
    if ($myfile_type=="text/plain") {

        // ������ ������ �� ����������� �� ����� ���������� �����.
        $data = implode("", file($myfile));

        // ������ �� ��������� �� "UTF-8", ��� �������� IE.
        $data_encoding = mb_convert_encoding($data, "UTF-8", "windows-1251");

        // ���������� ��� ��������.
        $user_agent = $_SERVER["HTTP_USER_AGENT"];

        // � ������� ����������� ��������, ������� ���������� ������
        $huffman = new Huffman();
        // ���� ������� IE, ��������� �� ����������� $data_encoding.
        if( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') !== false ||
            strpos($_SERVER['HTTP_USER_AGENT'],'rv:11.0') !== false) {
                $compressed = $huffman->compress($data_encoding);
        }
        else {
            $compressed = $huffman->compress($data);
        }

        // ������������� �������������� ������
        $huffman2 = new Huffman();
        $decompressed = $huffman2->decompress($compressed);

        // ���������� ����������� ������ � ���� $myfile.
        $fp = fopen($myfile, "w");
        fwrite($fp, $compressed);
        fclose($fp);

        // �������� ��������� ���� � ����� $newname_compr.
        copy($myfile, $newname_compr);

        // ������� ���������� ����� $myfile.
        file_put_contents($myfile, '');

        // ���������� ������������� ������ �� ��������� ���� $myfile.
        $fp = fopen($myfile, "w");
        fwrite($fp, $decompressed);
        fclose($fp);

        // �������� ��������� ���� � ����� $newname.
        copy($myfile, $newname);

        //�������� zip-������ �� �������
        $zip = new ZipArchive;
        if ($zip->open($newname_zip, ZipArchive::CREATE) === true) {
	        $zip->addFromString('info_compressed.txt', $compressed);
            $zip->addFromString('info.txt', $decompressed);
	        $zip->close();
        } else {
	        echo '�� ���� ������� �����!';
        }
    }
    }
}

// ��������� JSON-������, ��� �������� �� � js ����.
echo json_encode($newname.",".$newname_compr.",".$newname_zip);

?>