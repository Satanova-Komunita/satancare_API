<?php
/**
 * ProposalVote class is sending votes for proposal to Database
 */
class ProposalVote
{
  /**
   * Instance of connection to database
   *
   * @var Database
   */
  private $conn;

  /**
   * ID of voting member
   *
   * @var int
   */
  public $memberID;
  /**
   * Array of objects containing vote for each individual proposal
   *
   * @var array
   */
  public $votes;
  /**
   * Holds current datetime
   *
   * @var string
   */
  public $createdAt;

  /**
   * Initializes new instance of Sabat class
   *
   * @param Database $db
   */
  public function __construct($db)
  {
    $this->conn = $db;
  }

  /**
   * Adds votes to database
   *
   * @return array
   */
  public function addVotes ()
  {
    $query = "";

    // insert SQL command for inserting data to $query
    foreach ($this->votes as $vote)
    {
      $query .= "INSERT INTO Sabat_proposal_votes
        SET member_ID=".$this->memberID.",
            sabat_proposal_ID=".$vote->proposal_ID.",
            created_at=\"".$this->createdAt."\",
            value=".$vote->value.";";
    }

    if (!$this->canVote($this->votes[0]->proposal_ID))
    {
      return array(
        "status" => 503,
        "statusMsg" => "Service unavailable",
        "data" => array("message" => "Hlasování není povoleno.")
      );
    }
    else if ($this->hasNotVoted($this->votes[0]->proposal_ID))
    {
      $stmt = $this->conn->prepare($query);

      if ($stmt->execute())
        return array(
          "status" => 201,
          "statusMsg" => "Created",
          "data" => array("message" => "Hlas(y) byl(y) úspěšně zaznamenán(y).")
        );
      else
        return array(
          "status" => 503,
          "statusMsg" => "Service unavailable",
          "data" => array("message" => "Nepodařilo se zaznamenat hlas(y).")
        );
    }
    else
      return array(
        "status" => 503,
        "statusMsg" => "Service unavailable",
        "data" => array("message" => "Už si volil. Pozdě měnit svá rozhodnutí.")
      );

  }

  /**
   * Function determines whether sabat has enabled voting
   *
   * @param proposalID int
   * @return bool
   */
  private function canVote ($proposalID)
  {
    $query = "SELECT Sabats.voting_enabled as `votingEnabled`
      FROM Sabats
      LEFT JOIN Sabat_proposals on Sabats.ID=Sabat_proposals.sabat_ID
      WHERE Sabat_proposals.ID=?";

    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(1, $proposalID);

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['votingEnabled'] == 1) return true;
    return false;
  }

  /**
   * Function checks whether user already voted for this sabat
   *
   * @param proposalID int
   * @return bool
   */
  private function hasNotVoted ($proposalID)
  {
    $query = "SELECT created_at
      FROM Sabat_proposal_votes
      WHERE member_ID=? AND sabat_proposal_ID=?
      ORDER BY created_at DESC
      LIMIT 1";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(1, $this->memberID);
    $stmt->bindParam(2, $proposalID);

    $stmt->execute();

    $num = $stmt->rowCount();
    if ($num > 0)
      return false;
    else
      return true;
  }
}
?>
