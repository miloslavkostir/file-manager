<?php

class FileSystemTest extends TestCase
{

    /** @var xtrum\FileManager\Application\FileSystem */
    private $library;

    public function setUp()
    {
        parent::setUp();
        $this->library = new Ixtrum\FileManager\Application\FileSystem;
    }

    public function testCopy()
    {
        // Create test file
        $filePath = $this->uploadRoot . DIRECTORY_SEPARATOR . "test_file";
        file_put_contents($filePath, "data");

        // Test copy file in the same folder
        $this->library->copy($filePath, $this->uploadRoot);
        $this->assertFileEquals($filePath, $this->uploadRoot . DIRECTORY_SEPARATOR . "1_test_file");

        // Test copy file to a subfolder
        $dirPath = $this->uploadRoot . DIRECTORY_SEPARATOR . "test";
        mkdir($dirPath);
        $this->library->copy($filePath, $dirPath);
        $this->assertFileEquals($filePath, $dirPath . DIRECTORY_SEPARATOR . "test_file");

        // Test copy folder in the same folder
        $this->library->copy($dirPath, $this->uploadRoot);
        $this->assertFileEquals($this->uploadRoot . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file", $dirPath . DIRECTORY_SEPARATOR . "test_file");

        // Test copy folder in other folder
        $this->library->copy($this->uploadRoot . DIRECTORY_SEPARATOR . "1_test", $dirPath);
        $this->assertFileEquals($dirPath . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file", $this->uploadRoot . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file");
    }

    public function testCheckDuplName()
    {
        // Path exist
        $filename = pathinfo($this->uploadRoot, PATHINFO_BASENAME);
        $dirname = pathinfo($this->uploadRoot, PATHINFO_DIRNAME);
        $this->assertEquals("$dirname/1_$filename", $this->library->checkDuplName($this->uploadRoot));

        // Path does not exist
        $path = $this->uploadRoot . DIRECTORY_SEPARATOR . "test";
        $this->assertEquals($path, $this->library->checkDuplName($path));
    }

    public function testIsSubFolder()
    {
        $parent = $this->uploadRoot . DIRECTORY_SEPARATOR . "parent";
        mkdir($parent);
        $child = $parent . DIRECTORY_SEPARATOR . "child";
        mkdir($child);

        $this->assertTrue($this->library->isSubFolder($parent, $child));
        $this->assertFalse($this->library->isSubFolder($parent, $parent));
        $this->assertFalse($this->library->isSubFolder($child, $parent));
    }

    public function testGetRootName()
    {
        $this->assertEquals("/", $this->library->getRootName());
    }

}