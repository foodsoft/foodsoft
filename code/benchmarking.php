<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2026  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

//
// benchmarking.php
//

global $benchmark_enabled;
$benchmark_enabled = false;

global $benchmark_ts;
$benchmark_ts = [];

function benchmarkTimestamp($note = '') {
  global $benchmark_ts, $benchmark_enabled;
  if (!$benchmark_enabled) return;
  $ts = microtime(true);
  echo 'Timestamp', ($note ? " ({$note})": ''), ": {$ts}<br/>";
  array_push($benchmark_ts, [ $ts, $note ]);
}

function showBenchmark() {
  global $benchmark_ts, $benchmark_enabled;
  if (!$benchmark_enabled) return;
  $start = array_shift($benchmark_ts);
  if ($start == null) {
    echo 'No benchmark data available!<br/>';
    return;
  }
  echo 'Benchmark start', ($start[1] ? " ({$start[1]})" : ''), ": {$start[0]}<br/>";
  $previous = $start[0];
  foreach($benchmark_ts as $index => $ts) {
    $delta = $ts[0] - $previous;
    echo "Benchmark ({$index}", ($ts[1] ? " {$ts[1]}" : ''), "): ".sprintf('%.3lf', $delta)." ({$ts[0]})<br/>";
    $previous = $ts[0];
  }
  $delta = $previous - $start[0];
  echo "Benchmark total: {$delta}<br/>";
}
?>