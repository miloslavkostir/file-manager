<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

/**
 * Test FileSystem
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileSystemTest extends TestCase
{

    /** @var Ixtrum\FileManager\Application\FileSystem */
    private $library;

    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        $this->library = new Ixtrum\FileManager\Application\FileSystem;
    }

    /**
     * Test copy
     */
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

    /**
     * Test getUniquePath
     */
    public function testGetUniquePath()
    {
        // Path exist
        $filename = pathinfo($this->uploadRoot, PATHINFO_BASENAME);
        $dirname = pathinfo($this->uploadRoot, PATHINFO_DIRNAME);
        $this->assertEquals("$dirname/1_$filename", $this->library->getUniquePath($this->uploadRoot));

        // Path does not exist
        $path = $this->uploadRoot . DIRECTORY_SEPARATOR . "test";
        $this->assertEquals($path, $this->library->getUniquePath($path));
    }

    /**
     * Test isSubFolder
     */
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

    /**
     * Test getRootName
     */
    public function testGetRootName()
    {
        $this->assertEquals("/", Ixtrum\FileManager\Application\FileSystem::getRootName());
    }

}