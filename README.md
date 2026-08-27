# Exception-Free Benchmark

An advanced evaluation suite comprising **5 distinct testbeds** designed to stress-test the detection capabilities of automated security tools (SAST, DAST, and Grey-box Fuzzing) against "silent" and complex SQL Injection (SQLi) patterns. 

Traditional vulnerability scanners rely on visible oracle indicators (such as database error logs, HTTP 500 status codes, or unhandled exceptions) to confirm a vulnerability. The **Exception-Free Benchmark** targets the blind spots of these scanners by implementing realistic application architectures where SQL Injectability exists but is masked, swallowed, or fragmented.

---

## Detailed Testbeds

The 5 testbeds are classified into **3 core vulnerability patterns** based on how they mask exceptions or disrupt traditional taint flow:

### Pattern 1: System-Level Exception Masking
Exceptions and database error states are explicitly silenced or swallowed by API configurations, execution methods, or network interfaces, rendering traditional exception-based oracles useless.

#### Testbed 1: The Explicit Silent Mode
*   **Source:** [track.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%201/track.php)
*   **Sink:** [aggregate_stats.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%201/aggregate_stats.php)
*   **Description:** Simulates an internal Web Traffic Analytics system that collects User-Agent strings from client browsers to aggregate access metrics. Input data is initially stored safely via Prepared Statements; subsequently, a periodic background aggregation process retrieves this data and dynamically concatenates it into an analytical SQL query (Second-Order SQLi). This background process is explicitly configured with `PDO::ERRMODE_SILENT`, which suppresses database syntax errors without throwing exceptions, completely blinding exception-based oracles in DAST and Grey-Box Fuzzers.
*   **Root Cause:** Utilizes PDO::ERRMODE_SILENT to explicitly suppress database exceptions and completely conceal SQL syntax errors.
*   **POC Exploit:** `Evil_Agent', 1), ('hacked', 999)  ON DUPLICATE KEY UPDATE hit_count = hit_count  + 1 -- ) `

#### Testbed 2.1: The Batching Blackhole
*   **Source:** [register.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%202.1/register.php)
*   **Sink:** [billing.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%202.1/billing.php)
*   **Description:** Models an enterprise customer registration and loyalty reward synchronization system (Corporate Loyalty & Billing Dashboard). Customer names are sanitized and securely stored via Prepared Statements at the entry point. The vulnerability is triggered when retrieving the customer name to construct and execute a batched multi-query (`$db->multi_query()`). The resulting Second-Order SQLi occurs in a downstream query; however, because PHP MySQLi only exposes errors for the initial statement unless iterated via `mysqli_next_result()`, all downstream syntax errors are swallowed into an execution "blackhole" without producing exceptions.
*   **Root Cause:** Exploits the error-swallowing mechanism of mysqli_multi_query() for downstream queries when the application omits mysqli_next_result() iteration.
*   **POC Exploit:** `uet'), (0, 'hacked', 'uet') -- ) `

#### Testbed 2.2: The Asynchronous Socket Blackhole
*   **Endpoint:** [fast_log.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%202.2/fast_log.php)
*   **Description:** Simulates an asynchronous, high-throughput telemetry and metric logging service (Fast Metric Logging Service). The raw User-Agent header is directly concatenated into a dynamic SQL insert query (First-Order SQLi) and dispatched over the network socket using the asynchronous `MYSQLI_ASYNC` flag. Because the application omits calling `mysqli_reap_async_query()`, database error packets remain unread and permanently trapped in the OS TCP socket buffer. As a result, the HTTP server returns a clean 200 OK, completely blinding automated dynamic scanners and fuzzers.
*   **Root Cause:** Employs the asynchronous MYSQLI_ASYNC flag without invoking mysqli_reap_async_query(), leaving database error responses unread and trapped in the OS TCP socket buffer.
*   **POC Exploit:** `uet', NOW()), ('hacked', NOW()) -- )`

---

### Pattern 2: Intra-Application Taint Loss
Taint-flow tracking in static analysis (SAST) engines is broken by serialization, parsing, or structured data transformations within the application layer.

#### Testbed 3: The Serialization Taint-Loss
*   **Source:** [save_theme.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%203/save_theme.php)
*   **Sink:** [index.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%203/index.php)
*   **Description:** Simulates a CMS theme management application where user-supplied font settings are structured and serialized via `json_encode()` prior to secure database storage with Prepared Statements. At the rendering stage, the stored configuration is retrieved, deserialized using `json_decode()`, and dynamically interpolated into a logging SQL statement (Second-Order SQLi). This structured JSON serialization breaks the taint propagation graph (Taint Loss) in static analysis (SAST) engines, causing them to lose trace of untrusted data and miss the second-order vulnerability.
*   **Root Cause:** Employs json_encode() serialization for storage and json_decode() deserialization upon retrieval, breaking taint propagation graphs (Taint Loss) in static analysis tools.
*   **POC Exploit:** `Arial', NOW()), ('hacked', NOW()) -- )`

---

### Pattern 3: Distributed Context Fragmentation
The vulnerability is split across decoupled contexts (e.g., synchronous web requests and asynchronous background process executors), preventing end-to-end scanner detection.

#### Testbed 4: The Asynchronous Exfiltration Barrier
*   **Source:** [request_report.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%204/request_report.php)
*   **Sink Daemon:** [financial_worker.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%204/financial_worker.php)
*   **Status:** [check_status.php](file:///D:/benchmark/benchmark-second-order-sqli/src/testbed%204/check_status.php)
*   **Description:** Models an enterprise financial reporting and commission aggregation system. A synchronous Web API enqueues heavy calculation jobs into the database, while a decoupled background CLI Worker Daemon executes the complex dynamic queries asynchronously and exports the results to a CSV file retrieved via client polling. The SQLi trigger resides entirely within the isolated CLI worker and is encapsulated in an internal exception-handling block (`try-catch`), creating spatiotemporal barriers that simultaneously evade DAST (due to polling), SAST (due to inter-process boundaries), and CGF (due to process isolation and swallowed exceptions).
*   **Root Cause:** Introduces an inter-process boundary across an asynchronous job queue, preventing SAST from constructing cross-process CFGs/Call Graphs and isolating exceptions entirely within the CLI Worker. 
*   **POC Exploit:**

---

## Benchmark Environment Configuration

### 1. Prerequisites

*   Docker and Docker Compose installed on your system.

### 2. Benchmark Setup

1.  Clone the repository and navigate to the project directory:
    ```powershell
    cd benchmark-second-order-sqli
    ```
2.  Start the containers:
    ```powershell
    docker-compose up -d --build
    ```
3.  Access the interactive Dashboard at:
    `http://localhost:8888`

---

## SOTA Tool Evaluation

This benchmark evaluates state-of-the-art (SOTA) security testing tools—including **Static Application Security Testing (SAST)**, **Dynamic Application Security Testing (DAST)**, and **Grey-Box Fuzzers**—against the 5 Exception-Free Benchmark testbeds.

### Evaluation Criteria & Metrics

*   **Detection Rate:** The ability of the security tool to successfully identify the presence of the SQL Injection (SQLi) vulnerability in the testbed scenario.
*   **Source Attribution / Root-Cause Tracing:** The ability of the tool to accurately identify the untrusted entry point (Source parameter/endpoint), taint propagation path, and dangerous execution point (Sink endpoint).

**Evaluation Conventions:**
*   ✅ : **True Positive (TP)** — Successfully and accurately detected or traced the vulnerability.
*   ⚠️ : **False Positive (FP)** — Inaccurate alert, imprecise warning, or requiring manual heuristic configuration.
*   ❌ : **False Negative (FN)** — Completely missed the vulnerability (Blind spot).

---

### Experimental Results Table

<table>
  <thead>
    <tr>
      <th rowspan="2" align="center">No.</th>
      <th rowspan="2" align="center">Category</th>
      <th rowspan="2" align="left">Tool</th>
      <th colspan="2" align="center">Testbed 1<br><i>(Explicit Silent Mode)</i></th>
      <th colspan="2" align="center">Testbed 2.1<br><i>(Batching Blackhole)</i></th>
      <th colspan="2" align="center">Testbed 2.2<br><i>(Async Socket)</i></th>
      <th colspan="2" align="center">Testbed 3<br><i>(Serialization Loss)</i></th>
      <th colspan="2" align="center">Testbed 4<br><i>(Async Exfiltration)</i></th>
    </tr>
    <tr>
      <th align="center">Detection</th>
      <th align="center">Attribution</th>
      <th align="center">Detection</th>
      <th align="center">Attribution</th>
      <th align="center">Detection</th>
      <th align="center">Attribution</th>
      <th align="center">Detection</th>
      <th align="center">Attribution</th>
      <th align="center">Detection</th>
      <th align="center">Attribution</th>
    </tr>
  </thead>
  <tbody>
    <!-- SAST Tools -->
    <tr>
      <td align="center">1</td>
      <td rowspan="11" align="center"><b>SAST</b></td>
      <td>Zipper<sup><a href="#ref-1">[1]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">2</td>
      <td>TChecker<sup><a href="#ref-2">[2]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">3</td>
      <td>WHIP<sup><a href="#ref-3">[3]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">4</td>
      <td>RIPS<sup><a href="#ref-4">[4]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">5</td>
      <td>Yama<sup><a href="#ref-5">[5]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">6</td>
      <td>phpSAFE<sup><a href="#ref-6">[6]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">7</td>
      <td>TAP<sup><a href="#ref-7">[7]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">⚠️</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">8</td>
      <td>Progpilot<sup><a href="#ref-8">[8]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">9</td>
      <td>Joern<sup><a href="#ref-9">[9]</a></sup></td>
      <td align="center">⚠️</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">⚠️</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">10</td>
      <td>Psalm<sup><a href="#ref-10">[10]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">11</td>
      <td>Semgrep<sup><a href="#ref-11">[11]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">⚠️</td>
      <td align="center">❌</td>
    </tr>
    <!-- DAST Tools -->
    <tr>
      <td align="center">12</td>
      <td rowspan="3" align="center"><b>DAST</b></td>
      <td>sqlmap<sup><a href="#ref-12">[12]</a></sup></td>
      <td align="center">✅</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">13</td>
      <td>ZAProxy<sup><a href="#ref-13">[13]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">14</td>
      <td>Wapiti<sup><a href="#ref-14">[14]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <!-- Grey-box Fuzzing Tools -->
    <tr>
      <td align="center">15</td>
      <td rowspan="6" align="center"><b>Grey-box Fuzzing</b></td>
      <td>SQIRL<sup><a href="#ref-15">[15]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">16</td>
      <td>Witcher<sup><a href="#ref-16">[16]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">17</td>
      <td>NAVEX<sup><a href="#ref-17">[17]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">18</td>
      <td>Predator<sup><a href="#ref-18">[18]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">19</td>
      <td>Atropos<sup><a href="#ref-19">[19]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
    <tr>
      <td align="center">20</td>
      <td>Phuzz<sup><a href="#ref-20">[20]</a></sup></td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">✅</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
      <td align="center">❌</td>
    </tr>
  </tbody>
</table>

---

## References

<a id="ref-1"></a>
**[1]** C. Wang, P. Li, C. Luo, et al., "ZIPPER: Static Taint Analysis for PHP Applications with Precision and Efficiency," in *Proceedings of the 34th USENIX Security Symposium (USENIX Security '25)*, Seattle, WA, USA, 2025 *(Distinguished Paper Honorable Mention)*. [[Paper]](https://www.usenix.org/conference/usenixsecurity25/presentation/wang-xinyi) | [[GitHub Repo / Artifact]](https://zenodo.org/records/15636716)

<a id="ref-2"></a>
**[2]** C. Luo, P. Li, and W. Meng, "TChecker: Precise Static Inter-Procedural Analysis for Detecting Taint-Style Vulnerabilities in PHP Applications," in *Proceedings of the 2022 ACM SIGSAC Conference on Computer and Communications Security (CCS '22)*, Los Angeles, CA, USA, pp. 2151–2165, 2022. [[Paper]](https://dl.acm.org/doi/10.1145/3548606.3559391) | [[GitHub Repo]](https://github.com/cuhk-seclab/TChecker)

<a id="ref-3"></a>
**[3]** J. Al Kassar, L. Compagna, and D. Balzarotti, "WHIP: Improving Static Vulnerability Detection in Web Application by Forcing Tools to Collaborate," in *Proceedings of the 32nd USENIX Security Symposium (USENIX Security '23)*, Anaheim, CA, USA, pp. 2689–2706, 2023. [[Paper]](https://www.usenix.org/conference/usenixsecurity23/presentation/al-kassar) | [[GitHub Repo]](https://github.com/enferas/WHIP)

<a id="ref-4"></a>
**[4]** J. Dahse and T. Holz, "Simulation of Built-in PHP Features for Precise Static Code Analysis," in *Proceedings of the Network and Distributed System Security Symposium (NDSS '14)*, San Diego, CA, USA, 2014. [[Paper]](https://doi.org/10.14722/ndss.2014.23262) | [[GitHub Repo]](https://github.com/ripsscanner/rips)

<a id="ref-5"></a>
**[5]** J. Zou, et al., "Yama: Precise Opcode-Based Data Flow Analysis for Detecting PHP Applications Vulnerabilities," *IEEE Transactions on Information Forensics and Security (TIFS)*, Vol. 20, 2025. DOI: [10.1109/TIFS.2025.3592537](https://doi.org/10.1109/TIFS.2025.3592537). [[Paper]](https://doi.org/10.1109/TIFS.2025.3592537) | [[GitHub Repo]](https://github.com/xjzzzxx/Yama)

<a id="ref-6"></a>
**[6]** J. C. Fonseca and M. Vieira, "phpSAFE: A Security Analysis Tool for OOP Web Application Plugins," in *Proceedings of the 45th Annual IEEE/IFIP International Conference on Dependable Systems and Networks (DSN '15)*, Rio de Janeiro, Brazil, pp. 493–500, 2015. [[Paper]](https://www.academia.edu/30257975/phpSAFE_A_Security_Analysis_Tool_for_OOP_Web_Application_Plugins) | [[GitHub Repo]](https://github.com/JoseCarlos-Fonseca/phpSAFE)

<a id="ref-7"></a>
**[7]** F. Fang, J. Liu, et al., "TAP: A Static Analysis Model for PHP Vulnerabilities Based on Token and Deep Learning Technology," *PLOS ONE*, Vol. 14, No. 12, p. e0225196, 2019. [[Paper]](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0225196) | [[GitHub Repo]](https://github.com/das-lab/TAP)

<a id="ref-8"></a>
**[8]** E. Therond, "Progpilot: A Static Analysis Tool for Security Vulnerabilities in PHP Source Code," Design Security, 2017–2025. [[Documentation]](https://github.com/designsecurity/progpilot#documentation) | [[GitHub Repo]](https://github.com/designsecurity/progpilot)

<a id="ref-9"></a>
**[9]** F. Yamaguchi, N. Golde, D. Arp, and K. Rieck, "Modeling and Discovering Vulnerabilities with Code Property Graphs," in *Proceedings of the 2014 IEEE Symposium on Security and Privacy (S&P '14)*, San Jose, CA, USA, pp. 590–604, 2014. DOI: [10.1109/SP.2014.44](https://doi.org/10.1109/SP.2014.44). [[Paper]](https://doi.org/10.1109/SP.2014.44) | [[Documentation]](https://docs.joern.io) | [[GitHub Repo]](https://github.com/joernio/joern)

<a id="ref-10"></a>
**[10]** M. Brown, D. Gentili, et al. (Vimeo), "Psalm: A Static Analysis Tool for Finding Errors and Security Taint Flows in PHP Applications," 2019–2025. [[Documentation]](https://psalm.dev/docs/) | [[Security Docs]](https://psalm.dev/docs/security_analysis/) | [[GitHub Repo]](https://github.com/vimeo/psalm)

<a id="ref-11"></a>
**[11]** Semgrep Inc. (formerly Return To Corporation / r2c), "Semgrep: Fast, Lightweight Static Analysis for Finding Security Bugs and Enforcing Code Standards," 2020–2025. [[Documentation]](https://semgrep.dev/docs) | [[Rule Registry]](https://github.com/semgrep/semgrep-rules) | [[GitHub Repo]](https://github.com/semgrep/semgrep)

<a id="ref-12"></a>
**[12]** B. Damele A. G. and M. Stampar, "sqlmap: Automatic SQL Injection and Database Takeover Tool," 2006–2025. [[Documentation]](https://github.com/sqlmapproject/sqlmap/wiki) | [[Homepage]](https://sqlmap.org/) | [[GitHub Repo]](https://github.com/sqlmapproject/sqlmap)

<a id="ref-13"></a>
**[13]** S. Bennetts et al. (OWASP Foundation / Linux Foundation / Checkmarx), "OWASP Zed Attack Proxy (ZAP): The World's Most Widely Used Web App Scanner," 2010–2025. [[Documentation]](https://www.zaproxy.org/docs/) | [[Website]](https://www.zaproxy.org/) | [[GitHub Repo]](https://github.com/zaproxy/zaproxy)

<a id="ref-14"></a>
**[14]** D. del Pozo, N. Surribas, et al., "Wapiti: An Auditing Tool for Web Applications (Black-Box Vulnerability Scanner)," 2006–2025. [[Documentation / Homepage]](https://wapiti-scanner.github.io/) | [[GitHub Repo]](https://github.com/wapiti-scanner/wapiti)

<a id="ref-15"></a>
**[15]** S. Al Wahaibi, M. Foley, and S. Maffeis, "SQIRL: Grey-Box Detection of SQL Injection Vulnerabilities Using Reinforcement Learning," in *Proceedings of the 32nd USENIX Security Symposium (USENIX Security '23)*, Anaheim, CA, USA, pp. 2671–2688, 2023. [[Paper]](https://www.usenix.org/conference/usenixsecurity23/presentation/al-wahaibi) | [[GitHub Repo]](https://github.com/ICL-ml4csec/SQIRL)

<a id="ref-16"></a>
**[16]** E. Trickel, F. Pagani, C. Zhu, L. Dresel, G. Vigna, C. Kruegel, and Y. Shoshitaishvili, "Toss a Fault to Your Witcher: Applying Grey-Box Coverage-Guided Mutational Fuzzing to Detect SQL and Command Injection Vulnerabilities," in *Proceedings of the 44th IEEE Symposium on Security and Privacy (S&P '23)*, San Francisco, CA, USA, pp. 2486–2503, 2023. DOI: [10.1109/SP46215.2023.10179313](https://doi.org/10.1109/SP46215.2023.10179313). [[Paper]](https://ieeexplore.ieee.org/document/10179317/) | [[GitHub Repo]](https://github.com/sefcom/Witcher)

<a id="ref-17"></a>
**[17]** A. Alhuzali, R. Gjomemo, B. Eshete, and V. N. Venkatakrishnan, "NAVEX: Precise and Scalable Exploit Generation for Dynamic Web Applications," in *Proceedings of the 27th USENIX Security Symposium (USENIX Security '18)*, Baltimore, MD, USA, pp. 377–392, 2018. [[Paper]](https://www.usenix.org/conference/usenixsecurity18/presentation/alhuzali) | [[GitHub Repo]](https://github.com/aalhuz/navex)

<a id="ref-18"></a>
**[18]** C. Wang, W. Meng, C. Luo, and P. Li, "Predator: Directed Web Application Fuzzing for Efficient Vulnerability Validation," in *Proceedings of the 46th IEEE Symposium on Security and Privacy (S&P '25)*, San Francisco, CA, USA, 2025. [[Paper]](https://ieeexplore.ieee.org/document/11023300/) | [[GitHub Repo]](https://github.com/cuhk-seclab/Predator)

<a id="ref-19"></a>
**[19]** A. Güler, C. Aschermann, and A. Abbasi, "Atropos: Effective Fuzzing of Web Applications for Server-Side Vulnerabilities," in *Proceedings of the 33rd USENIX Security Symposium (USENIX Security '24)*, Philadelphia, PA, USA, pp. 3173–3190, 2024. [[Paper]](https://www.usenix.org/conference/usenixsecurity24/presentation/güler) | [[GitHub Repo]](https://github.com/CISPA-SysSec/atropos-legacy)

<a id="ref-20"></a>
**[20]** S. Neef, "What All the PHUZZ Is About: A Coverage-guided Fuzzer for Finding Vulnerabilities in PHP Web Applications," in *Proceedings of the 19th ACM Asia Conference on Computer and Communications Security (ASIA CCS '24)*, Singapore, pp. 495–508, 2024. DOI: [10.1145/3634737.3661137](https://doi.org/10.1145/3634737.3661137). [[Paper]](https://dl.acm.org/doi/10.1145/3634737.3661137) | [[GitHub Repo]](https://github.com/gehaxelt/phuzz)
