<?php

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

if(!isset($_POST['submit'])){
?>

<h2>Form upload file</h2>

<form action="upload.php" method="POST" enctype="multipart/form-data">
  Masukkan file yang ingin di upload ya:
  <input type="file" name="fileToUpload" id="fileToUpload" />
  <input type="submit" value="Upload File" name="submit" />
</form>

<?php    
}else{

    $fileToUpload = basename($_FILES["fileToUpload"]["name"]);
    $fileTemp = $_FILES["fileToUpload"]["tmp_name"];

    $AKUN = getenv('AZURE_BLOBS_AKUN');
    $KEY = getenv('AZURE_BLOBS_KEY');
    
    //$fileToUpload = "images-vision.jpg";
    //$fileToUpload = "HelloWorld.txt";
    
    //$connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');
    $connectionString = "DefaultEndpointsProtocol=https;AccountName=".$AKUN.";AccountKey=".$KEY;

    // Create blob client.
    $blobClient = BlobRestProxy::createBlobService($connectionString);

    if (!isset($_GET["Cleanup"])) {
        // Create container options object.
        $createContainerOptions = new CreateContainerOptions();

        $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

        // Set container metadata.
        $createContainerOptions->addMetaData("key1", "value1");
        $createContainerOptions->addMetaData("key2", "value2");

        //$containerName = "blockblobs".generateRandomString();
        //$containerName = $AKUN.'-'.generateRandomString();
        $containerName = $AKUN.'-uploads';

        try {
            // Create container.
            //$blobClient->createContainer($containerName, $createContainerOptions);

            // Getting local file so that we can upload it to Azure
            $myfile = fopen($fileTemp, "r") or die("Unable to open file!");
            fclose($myfile);
            
            # Upload file as a block blob
            echo "Uploading BlockBlob: ".PHP_EOL;
            echo $fileToUpload;
            echo "<br />";
            
            $content = fopen($fileTemp, "r");

            //Upload blob
            $blobClient->createBlockBlob($containerName, $fileToUpload, $content);

            // List blobs.
            $listBlobsOptions = new ListBlobsOptions();
            $listBlobsOptions->setPrefix($fileToUpload);

            echo "These are the blobs present in the container: <br />";

            do{
                $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                foreach ($result->getBlobs() as $blob)
                {
                    echo "- ".$blob->getName().": <a href='".$blob->getUrl()."'>".$blob->getUrl()."</a>";
                    $info = new SplFileInfo($blob->getName());
                    $imageFileType = $info->getExtension();
                    if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg")
                    {
                        echo "&nbsp;&nbsp;&nbsp;<a href='analyze.php?url=".$blob->getUrl()."' target='_blank'>Analyze Image</a>";
                    }
                    echo "<br/>";
                }
            
                $listBlobsOptions->setContinuationToken($result->getContinuationToken());
            } while($result->getContinuationToken());
            echo "<br />";
            echo "<br />";
            echo "<a href='index.php'>Home</a>&nbsp;&nbsp;&nbsp;";
            echo "<a href='upload.php'>Ulangi</a>";
        }
        catch(ServiceException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
        catch(InvalidArgumentTypeException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
    }
}
?>