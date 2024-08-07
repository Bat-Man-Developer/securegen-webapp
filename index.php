<?php
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;

    public function __construct($index, $timestamp, $data, $previousHash = '') {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->data = $data;
        $this->previousHash = $previousHash;
        $this->hash = $this->calculateHash();
    }

    public function calculateHash() {
        return hash('sha256', $this->index . $this->timestamp . $this->previousHash . json_encode($this->data));
    }
}

class Blockchain {
    public $chain;

    public function __construct() {
        $this->chain = array($this->createGenesisBlock());
    }

    public function createGenesisBlock() {
        return new Block(0, '2024-08-07', 'Genesis Block', '0');
    }

    public function getLatestBlock() {
        return $this->chain[count($this->chain) - 1];
    }

    public function addBlock($newBlock) {
        $newBlock->previousHash = $this->getLatestBlock()->hash;
        $newBlock->hash = $newBlock->calculateHash();
        $this->chain[] = $newBlock;
    }

    public function isChainValid() {
        for ($i = 1; $i < count($this->chain); $i++) {
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];

            if ($currentBlock->hash !== $currentBlock->calculateHash()) {
                return false;
            }

            if ($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }
        }

        return true;
    }
}

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "securegen_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create blocks table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS blocks (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `fldblockid` INT(11) NOT NULL,
    `fldblocktimestamp` VARCHAR(255) NOT NULL,
    `fldblockdata` TEXT NOT NULL,
    `fldblockpreviousHash` VARCHAR(255) NOT NULL,
    `fldblockhash` VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `fldusername` VARCHAR(255) NOT NULL,
    `flduseremail` VARCHAR(255) NOT NULL,
    `flduserphone` VARCHAR(255) NOT NULL,
    `flduseraddress` TEXT NOT NULL
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// Create a new blockchain
$myBlockchain = new Blockchain();

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = mysqli_real_escape_string($conn, $_POST['flduseramount']);
    $name = mysqli_real_escape_string($conn, $_POST['fldusername']);
    $email = mysqli_real_escape_string($conn, $_POST['flduseremail']);
    $phone = mysqli_real_escape_string($conn, $_POST['flduserphone']);
    $address = mysqli_real_escape_string($conn, $_POST['flduseraddress']);

    $newBlock = new Block(count($myBlockchain->chain), date('Y-m-d'), array('amount' => $amount));
    $myBlockchain->addBlock($newBlock);

    // Store the block in the database
    $sql = "INSERT INTO blocks (fldblockid, fldblocktimestamp, fldblockdata, fldblockpreviousHash, fldblockhash)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $newBlock->index, $newBlock->timestamp, json_encode($newBlock->data), $newBlock->previousHash, $newBlock->hash);
    if ($stmt->execute()) {
        $message = "Block added successfully!";
    } else {
        $message = "Error adding block: " . $stmt->error;
    }
    $stmt->close();

    // Store the user information in the database
    $sql = "INSERT INTO users (fldusername, flduseremail, flduserphone, flduseraddress)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $address);
    if ($stmt->execute()) {
        $message .= " User information stored successfully!";
    } else {
        $message .= " Error storing user information: " . $stmt->error;
    }
    $stmt->close();
} else {
    $message = "";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Blockchain Example</title>
    </head>
    <body>
        <h1>Blockchain Example</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            Amount: <input type="number" name="flduseramount"><br>
            Name: <input type="text" name="fldusername"><br>
            Email: <input type="email" name="flduseremail"><br>
            Phone: <input type="tel" name="flduserphone"><br>
            Address: <textarea name="flduseraddress"></textarea><br>
            <input type="submit" name="submit" value="Add to Blockchain">
        </form>

        <?php
        if ($message) {
            echo "<p style='color: green;'>" . $message . "</p>";
        }
        ?>

        <h2>Blockchain:</h2>
        <?php
        echo 'Is blockchain valid? ' . ($myBlockchain->isChainValid() ? 'true' : 'false') . '<br>';
        print_r($myBlockchain->chain);
        ?>
    </body>
</html>