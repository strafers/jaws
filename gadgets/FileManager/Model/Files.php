<?php
/**
 * FileManager Gadget
 *
 * @category    GadgetModel
 * @package     FileManager
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileManager_Model_Files extends Jaws_Gadget_Model
{
    /**
     * Fetches list of files
     *
     * @access  public
     * @param   int     $parent     Restrict result to a specified node
     * @param   bool    $published  If true then only published files are returned
     * @return  array   Array of files or Jaws_Error on error
     */
    function GetFiles($parent = null, $published = null)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        $fmTable->select('id', 'title', 'is_dir:boolean');

        if ($parent !== null){
            $fmTable->where('parent', $parent);
        }
        if ($published !== null){
            $fmTable->where('published', true);
        }
        return $fmTable->orderBy('id asc')->fetchAll();
    }

    /**
     * Fetches data of a file/dir
     *
     * @access  public
     * @param   int     $id  File ID
     * @return  mixed   Array of file data or Jaws_Error on error
     */
    function GetFile($id)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        $fmTable->select('id', 'parent', 'is_dir:boolean', 'title',
            'description', 'filename', 'url', 'published:boolean');
        return $fmTable->where('id', $id)->fetchRow();
    }

    /**
     * Fetches path of a file/dir
     *
     * @access  public
     * @param   int     $id     File or Directory ID
     * @param   array   $path   Directory hierarchy
     * @return  void
     */
    function GetPath($id, &$path)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        $fmTable->select('id', 'parent', 'title');
        $parent = $fmTable->where('id', $id)->fetchRow();
        if (!empty($parent)) {
            $path[] = array('id' => $parent['id'], 'title' => $parent['title']);
            $this->GetPath($parent['parent'], $path);
        }
    }

    /**
     * Inserts a new file/dir
     *
     * @access  public
     * @param   array  $data    File data
     * @return  mixed   True on successful insert, Jaws_Error otherwise
     */
    function InsertFile($data)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        return $fmTable->insert($data)->exec();
    }

    /**
     * Updates file/dir
     *
     * @access  public
     * @param   int     $id     File ID
     * @param   array   $data   File data
     * @return  mixed   True on successful update and Jaws_Error on error
     */
    function UpdateFile($id, $data)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        return $fmTable->update($data)->where('id', $id)->exec();
    }

    /**
     * Deletes file/dir
     *
     * @access  public
     * @param   int     $id  File ID
     * @return  mixed   Array of file data or Jaws_Error on error
     */
    function DeleteFile($id)
    {
        $fmTable = Jaws_ORM::getInstance()->table('fm_files');
        return $fmTable->delete()->where('id', $id)->exec();
    }
}