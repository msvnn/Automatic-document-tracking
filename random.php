<?php
// Composer autoload dosyasını dahil et
require '../vendor/autoload.php'; // Eğer Composer ile yüklendi

use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorPNG;

// Veritabanı bağlantısı
$pdo = new PDO('mysql:host=localhost;dbname=bysdb', 'root', '');

function generateNumericId($prefix = 'FTLP-', $length = 5) {
    $randomNumber = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    return $prefix . $randomNumber;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $uniqueId = $_POST['unique_id'];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE unique_id = ?");
        $stmt->execute([$uniqueId]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Duplicate unique ID detected. Try again.");
        }

        $stmt = $pdo->prepare("INSERT INTO documents (unique_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$uniqueId, $title, $content]);

        echo "Document saved successfully with ID: $uniqueId";

        $uniqueId = generateNumericId(); // Yeni benzersiz ID oluştur
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    $uniqueId = generateNumericId();
}

// Barcode Generator: HTML formatı
$generatorHTML = new BarcodeGeneratorHTML();
$barcodeHTML = $generatorHTML->getBarcode($uniqueId, $generatorHTML::TYPE_CODE_128);

// Barcode Generator: PNG formatı
$generatorPNG = new BarcodeGeneratorPNG();
$barcodePNG = $generatorPNG->getBarcode($uniqueId, $generatorPNG::TYPE_CODE_128);
file_put_contents('barcode.png', $barcodePNG); // PNG olarak kaydedin (isteğe bağlı)
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Registration</title>
</head>
<body>
    <h1>Document Registration</h1>
    <form method="post">
        <label for="unique_id">Generated Unique ID:</label><br>
        <input type="text" id="unique_id" name="unique_id" value="<?php echo $uniqueId; ?>" readonly><br><br>

        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" required><br><br>

        <label for="content">Content:</label><br>
        <textarea id="content" name="content" rows="4" cols="50" required></textarea><br><br>

        <button type="submit">Save Document</button>
    </form>

    <h2>Generated Barcode:</h2>
    <!-- Barkodu HTML olarak göster -->

        <?php echo $barcodeHTML; ?>





    <h3>Or PNG Format:</h3>
    <!-- Barkodu PNG olarak göster -->
    <img src="barcode.png" alt="Generated Barcode" width="10%">

    <?php
// Yazıcı listesini elle veya bir sorgu ile dinamik olarak oluşturabilirsiniz.
function getInstalledPrinters() {
    $printers = [];
    exec('wmic printer get name', $output); // Windows'taki yüklü yazıcıları listeleme
    foreach ($output as $line) {
        if (trim($line) !== '' && stripos($line, 'Name') === false) { // "Name" başlığını atla
            $printers[] = trim($line);
        }
    }
    return $printers;
}

$printers = getInstalledPrinters();

?>
    <h1>Barkod Yazdır</h1>
    <form method="post" action="print.php">
        <label for="printer">Yazıcı Seç:</label>
        <select id="printer" name="printer" required>
            <?php foreach ($printers as $printer): ?>
                <option value="<?php echo $printer; ?>"><?php echo $printer; ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <input type="hidden" name="barcode_data" value="FTLP-12345"> <!-- Barkod verisi -->
        <button type="submit">Yazdır</button>
    </form>
    
</body>
</html>
