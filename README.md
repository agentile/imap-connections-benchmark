Benchmarking IMAP connections under load using different languages.

Languages

 - Python (2.7)
 - PHP (5.3+)

Usage:

```
cp config.ini.example config.ini
# edit config.ini for your connection
bash benchmark.sh

```

Todo:

 - Simulate load/activity on the connections
 - Attempt raw stream (non-blocking) approaches with both PHP and Python
 - Add C implementation
 - Add multiple account support in ini, so that more than 15 connections can be spawned. (max 15 connections per gmail account)
 - Measure avg time to open connection in each implementation

