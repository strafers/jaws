<?php
/**
 * Forums Installer
 *
 * @category    GadgetModel
 * @package     Forums
 * @author      Ali Fazelzadeh <afz@php.net>
 * @copyright   2012-2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Forums_Installer extends Jaws_Gadget_Installer
{
    /**
     * Gadget Registry keys
     *
     * @var     array
     * @access  private
     */
    var $_RegKeys = array(
        array('topics_limit', '15'),
        array('posts_limit',  '10'),
        array('recent_limit',  '5'),
        array('date_format', 'd MN Y G:i'),
        array('edit_min_limit_time', '300'),
        array('edit_max_limit_time', '900'),
        array('enable_attachment', 'true'),
    );

    /**
     * Gadget ACLs
     *
     * @var     array
     * @access  private
     */
    var $_ACLKeys = array(
        'AddForum',
        'EditForum',
        'LockForum',
        'DeleteForum',
        'AddTopic',
        'PublishTopic',
        'EditTopic',
        'MoveTopic',
        'EditOthersTopic',
        'EditLockedTopic',
        'EditOutdatedTopic',
        'LockTopic',
        'DeleteTopic',
        'DeleteOthersTopic',
        'DeleteOutdatedTopic',
        'AddPost',
        'PublishPost',
        'AddPostAttachment',
        'AddPostToLockedTopic',
        'EditPost',
        'EditOthersPost',
        'EditPostInLockedTopic',
        'EditOutdatedPost',
        'DeletePost',
        'DeleteOthersPost',
        'DeleteOutdatedPost',
        'DeletePostInLockedTopic',
    );

    /**
     * Install the gadget
     *
     * @access  public
     * @return  mixed  Success with true and failure with Jaws_Error
     */
    function Install()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $result = $this->installSchema('insert.xml', '', 'schema.xml', true);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $new_dir = JAWS_DATA . 'forums';
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir));
        }

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  mixed  True on Success and Jaws_Error on Failure
     */
    function Uninstall()
    {
        $tables = array(
            'forums_posts',
            'forums_topics',
            'forums',
            'forums_groups',
            'forums_attachments'
        );
        $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $this->gadget->title);
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error($errMsg);
            }
        }

        return true;
    }

    /**
     * Upgrades the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  mixed   True on Success, Jaws_Error on Failure
     */
    function Upgrade($old, $new)
    {
        if (version_compare($old, '0.9.0', '<')) {
            $result = $this->installSchema('0.9.0.xml', '', '0.1.0.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $table = Jaws_ORM::getInstance()->table('forums_posts');
            // update post attachments count filed 
            $result = $table->update(array('attachments' => 1))->where('attachment_hits_count', 0, '>')->exec();
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // moving post attachment to separate table
            $table->select('id:integer', 'attachment_host_fname', 'attachment_user_fname', 'attachment_hits_count:integer');
            $table->where('attachment_hits_count', 0, '>');
            $result = $table->orderBy('id asc')->fetchAll();
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $attachModel = $this->gadget->model->load('Attachments');
            foreach ($result as $post) {
                $attachModel->InsertAttachments(
                    $post['id'],
                    array(array(
                        'user_filename' => $post['attachment_user_fname'],
                        'host_filename' => $post['attachment_host_fname'],
                        'host_filesize' => @filesize(JAWS_DATA. 'forums/'. $post['attachment_host_fname']),
                        'host_filetype' => @mime_content_type(JAWS_DATA. 'forums/'. $post['attachment_host_fname']),
                        'hitcount' => $post['attachment_hits_count'],
                    ))
                );
            }
        }

        if (version_compare($old, '1.0.0', '<')) {
            $result = $this->installSchema('schema.xml', '', '0.9.0.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // set dynamic ACLs of forums
            $fModel = $this->gadget->model->load('Forums');
            $forums = $fModel->GetForums(false, false);
            foreach ($forums as $forum) {
                $this->gadget->acl->insert('ForumAccess', $forum['id'], true);
                $this->gadget->acl->insert('ForumManage', $forum['id'], false);
            }
        }

        return true;
    }

}