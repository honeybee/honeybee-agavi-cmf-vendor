<?php

namespace Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton;

use Honeybee\FrameworkBinding\Agavi\CodeGen\Skeleton\SkeletonFinder;
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Finds the given skeleton and copies/renders all of the skeleton's files to
 * the target location using the specified data variables for rendering of
 * files as well as file and directory names.
 */
class SkeletonGenerator implements SkeletonGeneratorInterface
{
    const DIRECTORY_MODE = 0755;

    const TEMPLATE_FILENAME_EXTENSION = '.tmpl.twig';

    protected $data;
    protected $overwrite_enabled = true;
    protected $report = [];
    protected $reporting_enabled = true;
    protected $skeleton_name;
    protected $target_path;
    protected $twig_string_renderer = null;

    /**
     * Creates a new generator instance.
     *
     * @param string $skeleton_name name of the skeleton in one of the skeleton lookup paths
     * @param string $target_path full path to the target location
     * @param array $data variables to use as context for rendering via twig
     */
    public function __construct($skeleton_name, $target_path, array $data = [])
    {
        $this->skeleton_name = $skeleton_name;
        $this->target_path = $target_path;
        $this->fs = new Filesystem();
        $this->data = $data;

        $this->twig_string_renderer = TwigRenderer::create(
            [
                'twig_options' => [
                    'autoescape' => false,
                    'cache' => false,
                    'debug' => true,
                    'strict_variables' => true
                ]
            ]
        );
    }

    /**
     * Uses the current skeleton to create a file/folder structure in the
     * target location.
     * 1. create target folders returned from "getFolderStructure"
     * 2. copy all files from source to target (while creating necessary folders)
     * 3. render all ".tmpl.twig" files in the target folder to the same named files without that extension
     *
     * Override this method if you want to alter the default generator behaviour.
     */
    public function generate()
    {
        $this->createFolders();
        $this->copyFiles();
        $this->renderTemplates();
    }

    /**
     * Enables or disables the overwriting of files in their target location.
     */
    public function enableOverwriting($overwrite = true)
    {
        return $this->overwrite_enabled = (bool)$overwrite;
    }

    /**
     * Enables or disables report generation. A report is an array with string
     * messages of what was done by the generator.
     */
    public function enableReporting($report = true)
    {
        return $this->reporting_enabled = (bool)$report;
    }

    /**
     * @return array of string messages
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * Creates all folders specified by getFolderStructure within the target
     * location.
     */
    protected function createFolders()
    {
        foreach ($this->getFolderStructure() as $folder) {
            $new_folder = $this->twig_string_renderer->renderToString($folder, $this->data);
            $this->fs->mkdir($this->target_path . DIRECTORY_SEPARATOR . $new_folder, self::DIRECTORY_MODE);

            $msg = '[mkdir] ' . $this->target_path . DIRECTORY_SEPARATOR . $new_folder;
            if ($this->reporting_enabled) {
                $this->report[] =$msg;
            }
        }
    }

    /**
     * Copies all files from the source location to the target location.
     */
    protected function copyFiles()
    {
        $skeleton_finder = new SkeletonFinder();
        $source_path = $skeleton_finder->findByName($this->skeleton_name)->getRealpath();

        $finder = $this->getFinderForFilesToCopy($source_path);

        foreach ($finder as $file) {
            $target_file_path = $this->target_path . DIRECTORY_SEPARATOR . $file->getRelativePathname();
            $target_file_path = $this->twig_string_renderer->renderToString($target_file_path, $this->data);

            $this->fs->copy($file->getRealpath(), $target_file_path, $this->overwrite_enabled);

            $msg = '[copy] ' . $file->getRealpath() . ' => ' . $target_file_path;

            if ($this->reporting_enabled) {
                $this->report[] = $msg;
            }
        }
    }

    /**
     * @param string $source_path path to copy files from
     *
     * @return Finder instance configured with all files to copy from the source path
     */
    protected function getFinderForFilesToCopy($source_path)
    {
        $finder = new Finder();
        $finder->files()->notName(SkeletonFinder::VALIDATION_FILE)->in($source_path);
        return $finder;
    }

    /**
     * Renders all files within the target location whose extension is
     * ".tmpl.twig" onto a file that has the same name without that extension.
     * After the rendering all the ".tmpl.twig" files will be deleted in the
     * target location.
     */
    protected function renderTemplates()
    {
        $finder = new Finder();

        $finder->files()->name('*' . self::TEMPLATE_FILENAME_EXTENSION)->in($this->target_path);

        $twig_renderer = TwigRenderer::create(
            [
                'twig_options' => [
                    'autoescape' => false,
                    'cache' => false,
                    'debug' => true,
                    'strict_variables' => true
                ],
                'template_paths' => [
                    $this->target_path
                ]
            ]
        );

        foreach ($finder as $template) {
            $target_file_path = $template->getPath() . DIRECTORY_SEPARATOR .
                $template->getBasename(self::TEMPLATE_FILENAME_EXTENSION);

            if (!file_exists($target_file_path) || (is_readable($target_file_path) && $this->overwrite_enabled)) {
                $twig_renderer->renderToFile(
                    $template->getRelativePathname(),
                    $target_file_path,
                    $this->data
                );
            }

            $msg = '[render] ' . $template->getRelativePathname() . ' => ' . $target_file_path;

            if ($this->reporting_enabled) {
                $this->report[] = $msg;
            }
        }

        $this->fs->remove($finder);
    }

    /**
     * Returns an array of folders to create in the target path.
     * The directories can be deeply nested and contain twig code that gets
     * rendered via the simple twig renderer. That means you can return an
     * array with this as an example string:
     * 'some/deeply/nested/{{module_name}}/{{namespace|replace({"\\\\":"/"})}}/assets'
     * if "module_name" and "namespace" are known string parameters.
     *
     * @return array of folders to create (relative paths)
     */
    protected function getFolderStructure()
    {
        return [];
    }
}
