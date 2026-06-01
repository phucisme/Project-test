<?php
class Friends {
    private $db;
    private $user_id;

    public function __construct($db, $user_id) {
        $this->db = $db;
        $this->user_id = $user_id;
    }

    public function getFriends() {
        $query = "SELECT u.id, u.username, u.level, u.garden_width, u.garden_height
                  FROM friendships f
                  JOIN users u ON (f.friend_id = u.id OR f.user_id = u.id)
                  WHERE (f.user_id = :user_id OR f.friend_id = :user_id)
                  AND u.id != :user_id
                  AND f.status = 'accepted'
                  ORDER BY u.username";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFriend($friend_id) {
        if ($friend_id == $this->user_id) {
            return array('success' => false, 'error' => 'Cannot add yourself');
        }

        // Check if already friends
        $query = "SELECT * FROM friendships 
                  WHERE (user_id = :user_id AND friend_id = :friend_id) 
                  OR (user_id = :friend_id AND friend_id = :user_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['status'] == 'accepted') {
                return array('success' => false, 'error' => 'Already friends');
            }
            if ($existing['status'] == 'pending') {
                // Accept friendship
                if ($existing['user_id'] == $friend_id) {
                    $query = "UPDATE friendships SET status = 'accepted', accepted_at = NOW() WHERE id = :id";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':id', $existing['id']);
                    $stmt->execute();
                    return array('success' => true, 'message' => 'Friendship accepted');
                } else {
                    return array('success' => false, 'error' => 'Request already pending');
                }
            }
        }

        // Create new friendship
        $query = "INSERT INTO friendships (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'pending')";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->execute();

        return array('success' => true);
    }

    public function removeFriend($friend_id) {
        $query = "DELETE FROM friendships 
                  WHERE (user_id = :user_id AND friend_id = :friend_id) 
                  OR (user_id = :friend_id AND friend_id = :user_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':friend_id', $friend_id);
        $stmt->execute();

        return array('success' => true);
    }

    public function viewFriendGarden($friend_id) {
        $query = "SELECT id, username, level, garden_width, garden_height FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $friend_id);
        $stmt->execute();
        $friend = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$friend) {
            return array('success' => false, 'error' => 'Friend not found');
        }

        $query = "SELECT * FROM garden_plots WHERE user_id = :user_id ORDER BY y, x";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $friend_id);
        $stmt->execute();
        $garden = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array('success' => true, 'friend' => $friend, 'garden' => $garden);
    }

    public function getSuggestedFriends($limit = 5) {
        $query = "SELECT id, username, level FROM users 
                  WHERE id != :user_id 
                  AND id NOT IN (
                    SELECT friend_id FROM friendships WHERE user_id = :user_id
                    UNION
                    SELECT user_id FROM friendships WHERE friend_id = :user_id
                  )
                  ORDER BY level DESC, RAND()
                  LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
