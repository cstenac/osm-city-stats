--- vim: ts=2

--- Step 1 of the analysis: some indexes

-- Fast access for nodes, ways with no tags
ALTER TABLE nodes add tags_count INTEGER; CREATE INDEX nodes_tags_count ON nodes(tags_count);
ALTER TABLE ways add tags_count INTEGER; CREATE INDEX ways_tags_count ON ways(tags_count);

UPDATE nodes SET tags_count = array_length(avals(tags), 1);
UPDATE ways SET tags_count = array_length(avals(tags), 1);
