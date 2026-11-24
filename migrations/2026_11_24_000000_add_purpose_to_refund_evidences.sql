SET @add_refund_evidence_purpose = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
            AND table_name = 'refund_evidences'
            AND column_name = 'purpose'
    ),
    'SELECT 1',
    'ALTER TABLE refund_evidences ADD COLUMN purpose VARCHAR(255) NOT NULL DEFAULT ''evidence'' AFTER refund_request_id'
);
PREPARE add_refund_evidence_purpose_stmt FROM @add_refund_evidence_purpose;
EXECUTE add_refund_evidence_purpose_stmt;
DEALLOCATE PREPARE add_refund_evidence_purpose_stmt;
