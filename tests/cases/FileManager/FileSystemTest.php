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

    /** @var Ixtrum\FileManager\FileSystem */
    private $library;

    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        $this->library = new Ixtrum\FileManager\FileSystem;
    }

    /**
     * Test copy
     */
    public function testCopy()
    {
        // Create test file
        $filePath = $this->dataDir . DIRECTORY_SEPARATOR . "test_file";
        file_put_contents($filePath, "data");

        // Test copy file in the same directory
        $this->library->copy($filePath, $this->dataDir);
        $this->assertFileEquals($filePath, $this->dataDir . DIRECTORY_SEPARATOR . "1_test_file");

        // Test copy file to a sub-directory
        $dirPath = $this->dataDir . DIRECTORY_SEPARATOR . "test";
        mkdir($dirPath);
        $this->library->copy($filePath, $dirPath);
        $this->assertFileEquals($filePath, $dirPath . DIRECTORY_SEPARATOR . "test_file");

        // Test copy directory in the same directory
        $this->library->copy($dirPath, $this->dataDir);
        $this->assertFileEquals($this->dataDir . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file", $dirPath . DIRECTORY_SEPARATOR . "test_file");

        // Test copy directory in other directory
        $this->library->copy($this->dataDir . DIRECTORY_SEPARATOR . "1_test", $dirPath);
        $this->assertFileEquals($dirPath . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file", $this->dataDir . DIRECTORY_SEPARATOR . "1_test" . DIRECTORY_SEPARATOR . "test_file");
    }

    /**
     * Test getUniquePath
     */
    public function testGetUniquePath()
    {
        // Path exist
        $filename = pathinfo($this->dataDir, PATHINFO_BASENAME);
        $dirname = pathinfo($this->dataDir, PATHINFO_DIRNAME);
        $this->assertEquals("$dirname/1_$filename", $this->library->getUniquePath($this->dataDir));

        // Path does not exist
        $path = $this->dataDir . DIRECTORY_SEPARATOR . "test";
        $this->assertEquals($path, $this->library->getUniquePath($path));
    }

    /**
     * Test isSubDir
     */
    public function testIsSubDir()
    {
        $parent = $this->dataDir . DIRECTORY_SEPARATOR . "parent";
        mkdir($parent);
        $child = $parent . DIRECTORY_SEPARATOR . "child";
        mkdir($child);

        $this->assertTrue($this->library->isSubDir($parent, $child));
        $this->assertFalse($this->library->isSubDir($parent, $parent));
        $this->assertFalse($this->library->isSubDir($child, $parent));
    }

    /**
     * Test getRootName
     */
    public function testGetRootName()
    {
        $this->assertEquals("/", Ixtrum\FileManager\FileSystem::getRootName());
    }

}