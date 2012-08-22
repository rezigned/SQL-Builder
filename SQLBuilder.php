<?php
/**
 * SQL Builder library - Construct SQL Statement in a simpler style.
 *
 * @copyright  Copyright (c) 2011 MAQE
 * @author     @rezigned
 * @license    Licensed under the MIT License
 * @version    0.1.1
 * @package    SQL
 */

/**
 * Quick overview & usage
 * 
 * $q = new SQLBuilder('users', 'u')
 * 
 * - Simple where clause condition
 * $q->filter('username', 'admin')
 * $q->to_sql() will compose an sql 
 * >> SELECT * FROM users u WHERE username=?
 * 
 * - More complex where condition
 * $q->filter('date_created', '<', time())
 * $q->to_sql() 
 * >> SELECT * FROM users u WHERE date_created < ?
 * 
 * - Arbitary condition
 * $q->filter('u.setting_id IS NOT NULL')
 * >> SELECT * FROM users u WHERE u.setting IS NOT NUL
 */
class SQLBuilder {
              /**
               * Base table
               *
               * @var array 
               */
    protected $table,
              /**
               * @var array
               */
              $limit,
              /**
               * @var array
               */
              $group,
              $having,
              $orders  = array(),
              $selects = array(),
              $joins   = array(),
              $wheres  = array(),
              $params  = array();
    
    /**
     *
     * @param type $table Main table name
     * @param type $alias An alias for your main table
     */
    public function __construct($table, $alias = null) {
        $this->table = compact('table', 'alias');
    }
    
    /**
     * Filter your result out by various conditions
     * 
     * @param  string     $key    
     * @param  string     $op
     * @param  string     $val
     * @return SQLBuilder 
     * 
     * @example
     * 
     *   filter('user', 'admin')         => user  = 'admin'
     *   filter('user', '!=', 'admin')   => user != 'admin'
     *   filter('custom_id < 1')         
     */
    public function filter($key, $op = '=', $val = null) {

        $num_args = func_num_args() ;
        
        # use arbitary style
        if ($num_args == 1) {
            $op = null;
        }
        
        # use '='
        if ($num_args == 2) {
            $val = $op;
            $op  = '=';
        }
            
        # else use custom operator e.g. <, >
        $this->wheres[] = array('col' => $key, 'op' => $op, 'val' => $val);
        
        return $this;
    }
    
    /**
     * Compile sql statement and group all params into $params array
     * 
     * @return string
     */
    public function generate_sql_filter() {
        
        $sql = array();
        foreach($this->wheres as $w) {
            
            # use raw query (no params)
            if (empty($w['op']) && empty($w['val']))
                $sql[] = $w['col'];
            
            # normal where clause key = ?
            else {
                
                $sql[] = "{$w['col']} {$w['op']} ?";
                $this->params[] = $w['val'];
            }
        }
        
        return $sql ? join(' AND ', $sql) : null;
    }
    
    /**
     * Add `order by` clause
     * 
     * @param string $order 
     * 
     * @example  
     * 
     *      $q->order('username ASC')
     * 
     * @return SQLBuilder 
     */
    public function order($order) {
        $this->orders[] = $order;
        
        return $this;
    }
    
    /**
     * Add limit and offset to your query
     * 
     * @param  int  $limit  
     * @param  int  $offset
     * 
     * @example 
     *      $q->limit(5)    # result in LIMIT 0, 5
     *      $q->limit(5, 1) # result in LIMIT 1, 5
     * 
     * @return SQLBuilder 
     */
    public function limit($limit, $offset = 0) {
        $this->limit = array($limit, $offset);
        
        return $this;        
    }
    
    /**
     * Add group by clause
     * 
     * @param string $group
     * 
     * @example 
     *      $q->group('user')
     * 
     * @return SQLBuilder 
     */
    public function group($group) {
        $this->group = $group;
        
        return $this;
    }

    /**
     * Add select statement 
     * 
     * @param type $col
     * 
     * @example 
     *      $q->select('username', 'date_created')
     * 
     * @return SQLBuilder 
     */
    public function select($col) {
        
        $this->selects[] = $col;
        
        return $this;
    }
    
    /**
     * Perform a join clause
     * 
     * @param  string     $table
     * @param  string     $condition
     * @param  string     $type
     * @return SQLBuilder 
     * 
     * @example join('group_members', 'g', 'g.group_id=m.member_id')
     */
    public function join($table, $alias, $condition, $type = 'INNER') {
        
        $this->joins[$alias] = array(
            'table' => $table,
            'alias' => $alias,
            'cond'  => $condition,
            'type'  => $type,
        );
        
        return $this;
    }
    
    /**
     * Compile sql in to SQL statement
     * 
     * @return string 
     */
    public function to_sql() {

        # FROM
        $sql = sprintf(' FROM %s %s ', $this->table['table'], $this->table['alias']);
        
        # JOINS
        $joins = array();
        foreach($this->joins as $j) {
            $joins[] = sprintf(' %s JOIN %s %s ON %s ', 
                           $j['type'],
                           $j['table'],
                           $j['alias'],
                           $j['cond']
                       );
        }
        
        $sql .= join(' ', $joins);
        
        # WHERE 
        $where = $this->generate_sql_filter();
        if ($where)
            $sql .= 'WHERE ' . $this->generate_sql_filter();

        # GROUP
        if ($this->group)
            $sql .= ' GROUP BY ' . $this->group;
        
        # HAVING
        if ($this->having)
            $sql .= ' HAVING ' . $this->having;
            
        # ORDER 
        if ($this->orders)
            $sql .= ' ORDER BY ' . join(', ', $this->orders);
        
        # LIMIT
        if ($this->limit)
            $sql .= ' LIMIT ' . $this->limit[1] . ', ' . $this->limit[0];

        # SELECT
        if (!$this->selects)
            $this->selects[] = '*';
        
        $sql = 'SELECT ' . join(', ', $this->selects) . $sql;

        return $sql;
    }
    
    /**
     * Return all params for this query
     * 
     * @return array 
     */
    public function params() {
        return $this->params;
    }
    
    /**
     * Reset all properties to initial state
     */
    public function reset() { 

        foreach(get_class_vars($this) as $key)
            $this->$key = array();
    }
    
    /**
     * Another way to get an sql by evaulate it as string.
     * 
     * @example (string)$sql
     * @return  string 
     */
    public function __toString() {
        return $this->to_sql();
    }
}