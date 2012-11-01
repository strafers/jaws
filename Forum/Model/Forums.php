<?php
/**
 * Forum Gadget
 *
 * @category    GadgetModel
 * @package     Forum
 * @author      Ali Fazelzadeh <afz@php.net>
 * @author      Hamid Reza Aboutalebi <abt_am@yahoo.com>
 * @copyright   2012 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Forum_Model_Forums extends Jaws_Gadget_Model
{
    /**
     * Returns array of forum properties
     *
     * @access  public
     * @param   int     $fid    forum ID
     * @return  mixed   Array of forum properties or Jaws_Error on error
     */
    function GetForum($fid)
    {
        $params = array();
        $params['fid'] = $fid;

        $sql = '
            SELECT
                [id], [gid], [title], [description], [fast_url], 
                [last_post_id], [last_post_time], [order], [locked], [published]
            FROM [[forums]]
            WHERE [id] = {fid}';

        $types = array(
            'integer', 'integer', 'text', 'text', 'text',
            'integer', 'timestamp', 'integer', 'boolean', 'boolean'
        );

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        return $result;
    }

    /**
     * Returns a list of  forums at a request level
     *
     * @access  public
     * @param   int     $gid            Group ID
     * @param   bool    $onlyPublished
     * @param   bool    $last_post_info
     * @return  mixed   Array with all the available forums or Jaws_Error on error
     */
    function GetForums($gid, $onlyPublished = false, $last_post_info = false)
    {
        $params = array();
        $params['gid'] = $gid;
        $params['published'] = true;

        if ($last_post_info) {
            $sql = '
                SELECT
                    [[forums]].[id], [[forums]].[title], [[forums]].[description],
                    [[forums]].[fast_url], [[forums]].[topics], [[forums]].[posts],
                    [last_post_id], [last_post_time], [[users]].[username], [[users]].[nickname],
                    [[forums]].[locked], [[forums]].[published]
                FROM [[forums]]
                LEFT JOIN [[forums_posts]] ON [[forums]].[last_post_id] = [[forums_posts]].[id]
                LEFT JOIN [[users]] ON [[forums_posts]].[uid] = [[users]].[id]
                WHERE [gid] = {gid}';

            $types = array(
                'integer', 'text', 'text',
                'text', 'integer', 'integer',
                'integer', 'timestamp', 'text', 'text',
                'boolean', 'boolean'
            );
        } else {
            $sql = '
                SELECT
                    [id], [title], [description], [fast_url], [topics], [posts],
                    [locked], [published]
                FROM [[forums]]
                WHERE [gid] = {gid}';

            $types = array(
                'integer', 'text', 'text', 'text', 'integer', 'integer',
                'boolean', 'boolean'
            );
        }

        if ($onlyPublished) {
            $sql .= ' AND [[forums]].[published] = {published}';
        }
        $sql.= ' ORDER BY [[forums]].[order] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        return $result;
    }

    /**
     * Update last_post_id, last_post_time and count of replies
     *
     * @access  public
     * @param   int         $fid                Forum ID
     * @param   int         $last_post_id       Last post ID
     * @param   timestamp   $last_post_time     Last post time
     * @return  mixed       Jaws_Error on failure
     */
    function UpdateForumStatistics($fid, $last_post_id, $last_post_time)
    {
        $params = array();
        $params['fid']            = (int)$fid;
        $params['last_post_id']   = $last_post_id;
        $params['last_post_time'] = $last_post_time;

        $sql = '
            UPDATE [[forums]] SET
                [last_post_id]   = {last_post_id},
                [last_post_time] = {last_post_time},
                [topics] = (
                    SELECT COUNT([[forums_topics]].[id])
                    FROM [[forums_topics]]
                    WHERE [[forums_topics]].[fid] = {fid}
                ),
                [posts] = (
                    SELECT COUNT([[forums_posts]].[id])
                    FROM [[forums_posts]]
                    RIGHT JOIN [[forums_topics]] ON [[forums_posts]].[tid] = [[forums_topics]].[id]
                    WHERE [[forums_topics]].[fid] = {fid}
                )
            WHERE [id] = {fid}';

        $result = $GLOBALS['db']->query($sql, $params);
        return $result;
    }

}