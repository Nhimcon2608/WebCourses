<?php
// static_course_detail.php
session_start();
// (Tùy chọn) Kiểm tra đăng nhập nếu cần

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Mảng dữ liệu tĩnh cho các khóa học
$staticCourseResources = [
  // 1. Xây dựng hệ thống bảo vệ thông tin (36 mục)
  1 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1k_Db3SeT_tt4KqVobCbNZgvZAH3jw3Y-/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'VIDEO 1',
      'url'   => 'https://drive.google.com/file/d/13T-0vu6nzrGADP82umEkEXI74Rs7gt8M/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1aOwVGq6kvi2SRONEx64ver-p2x_ykBsi/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/13T-0vu6nzrGADP82umEkEXI74Rs7gt8M/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3',
      'url'   => 'https://drive.google.com/file/d/1ATpyTl-2QkTUhPimBeAfr-ttwj6s756B/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3',
      'url'   => 'https://drive.google.com/file/d/1IpDpkGUd4V9Sw8kvNkrfGzYltVvW9y6x/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/107T3KRMCgzwfxP1MWoTHduHnJPSbA2em/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4',
      'url'   => 'https://drive.google.com/file/d/1tmDv5xFsEe17uIjzZuyq25IxLi3Zfcio/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 5',
      'url'   => 'https://drive.google.com/file/d/16SXG0N7faKexIvkfbLbMko1MJixpnDEP/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5',
      'url'   => 'https://drive.google.com/file/d/1vKrAOpF3x4MBgI8HtcvPPpy6Cf4Pu8y5/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 6',
      'url'   => 'https://drive.google.com/file/d/1V2UyoC9OzIP9MSGBYPBmQoLavWtJZURI/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 6',
      'url'   => 'https://drive.google.com/file/d/108Wjp0X5sSZkGu3XqL_Ok4RpbkxTZkOH/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 8',
      'url'   => 'https://drive.google.com/file/d/18UMfOVdWCXkr_W965ctfLHz41Odp4E4U/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 8',
      'url'   => 'https://drive.google.com/file/d/1HXpsa2RiPfG0FI8cAiWQyE6smlGvukNP/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 9',
      'url'   => 'https://drive.google.com/file/d/1iiut_U81UsntF6IAcotJV86w3FEOx1MI/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 9',
      'url'   => 'https://drive.google.com/file/d/1Y3z5JaurTYseGdKsJ6CR2jlRcGIqbwc3/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 10',
      'url'   => 'https://drive.google.com/file/d/1bzoCJl3wa2ZL5CDixwSYA5rp9rGZGdpU/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 10',
      'url'   => 'https://drive.google.com/file/d/1OBh2KcxyHeRd3bHMIM7A1LKJgd0dQge2/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 13',
      'url'   => 'https://drive.google.com/file/d/1iUHL5pBSruo9Rbl5-RJb9kI8yQ0JKZZ1/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 13',
      'url'   => 'https://drive.google.com/file/d/1aI4_y6dCt0ZMZ9NaVNItIwMYKkqLc5lD/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chuong 14',
      'url'   => 'https://drive.google.com/file/d/17Vu4JsR6cE5vTtGX0PuCCL79ktGisyPH/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 14',
      'url'   => 'https://drive.google.com/file/d/14wIr7sanZ-FvN1L22S6I12z3NtErq32V/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chuong 16',
      'url'   => 'https://drive.google.com/file/d/13dxryLzQVgcVGqCZYNgTdWZ0c0wDLlIJ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 16',
      'url'   => 'https://drive.google.com/file/d/1hhPaUj1pbcseslS2J89TetJuBd7k3O8K/preview'
    ],
    
  ],
  // 2. Phân tích tài chính (10 mục)
  2 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1B_NZFV1-KCgeh95L3xqjEOvJYwv78AtO/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1',
      'url'   => 'https://drive.google.com/file/d/1sy7ZQ1GslxspIpzu8bGA-uoHVOUiykVT/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1Bd3gbjL0wfYc4roRQ9Kei9wrUdYlGvKq/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/1RboYjvWuZfIXW10d3MurbGj7q6Bp_bag/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 5',
      'url'   => 'https://drive.google.com/file/d/1K4f9x9NFWNNJB5G6IdQkdazxlG7hC88H/preview'
    ],
    [
      'type'  => 'Bài Tập',
      'url'   => 'https://drive.google.com/file/d/1klN_k62oGQlG5IvnXrHVFl4ENfBWU840/preview'
    ],
    
  ],
  // 3. Mô hình hóa phần mềm (20 mục)
  3 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1_5ZVyBdqZunDMsV0f-adEpimHtC3B3AB/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1',
      'url'   => 'https://drive.google.com/file/d/1GW_a903_p1HYDB2CZpB_Ss5hZQChQHw_/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/19IN2idQKCtO80leRUuorVHvw5E_Hpljg/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/1KcFsdPdp3FYnipVtFViAdD7maGJ9booL/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3',
      'url'   => 'https://drive.google.com/file/d/1kmoGJPfeFotQ_53Hh4oY2qiwGS-F1BeF/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.1',
      'url'   => 'https://drive.google.com/file/d/1cOiaHGcVpLxhYC5_vxflxpJNcP-e2yij/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/1QCBkiDABhhyAGSl5CllV5swDSUtE9llg/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/1cgiCzGaPq9UNZ7YsDCDMI4Ovq-k4MKhm/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4',
      'url'   => 'https://drive.google.com/file/d/12K1h1p0N916bqeYjczAJJpKF14A_AnJg/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 5',
      'url'   => 'https://drive.google.com/file/d/1MDHS2xBMyYIF6CBUNuC3fBJTYfjmHqlB/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.1',
      'url'   => 'https://drive.google.com/file/d/1zcFR48YNT4LNIEcqCg4i546tCrxaFQNZ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.2',
      'url'   => 'https://drive.google.com/file/d/16LWVm_ULrfQ-aMi7UhbmDrf4uHFD5ZzZ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.3',
      'url'   => 'https://drive.google.com/file/d/1UgcWM38NFRWnTSkmTCQQfh4Ia80UYydc/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.4',
      'url'   => 'https://drive.google.com/file/d/1YqLmQS8yVTvmB9cgLjj08lGGbTkcsnym/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.5',
      'url'   => 'https://drive.google.com/file/d/1Lw6Hy_rfKtGnfqDajdWzsq9VJROYOENx/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5.6',
      'url'   => 'https://drive.google.com/file/d/1Uh5_OscBKBw8x4fW_CFQCP_IXQNsESIC/preview'
    ],
    
  ],
  // 4. Lý thuyết mật mã (25 mục)
  4 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1BMvCx80AYhZ8jHQGA8AMZlWtOKLFOSD9/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1',
      'url'   => 'https://drive.google.com/file/d/15ZB5zQPKIzx6TKv_ztifkMVCsrXCnRHE/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1hjtYK1QmCLfcmSWn52BOyXNjKW0YAMyS/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.1',
      'url'   => 'https://drive.google.com/file/d/1HfLr_5-OKDoboB_bgJbJ6fmDNUPTSG4_/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.2',
      'url'   => 'https://drive.google.com/file/d/1cDJiz013Ej5A-YFei52PeBgenzQfaPL0/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3',
      'url'   => 'https://drive.google.com/file/d/1Ju4mPhzjRPvsoiziAQotql4wfBvSGbWU/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.1',
      'url'   => 'https://drive.google.com/file/d/128n2Riz9mm2VAM1J5pQWTsLyc5SSz_ff/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/1hDG9V_a6E0s2mOZ4lXN3D90FTmAxN4WS/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/1YIxpbo5ykFzZj953lzW_yNk4Y4iSQ9-z/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.1',
      'url'   => 'https://drive.google.com/file/d/1ayKqUBZTeaLX-6PoF4TVfNVeuugWUm9s/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.2',
      'url'   => 'https://drive.google.com/file/d/1SIQrTOrzK4YKZMktZshXAKAXR_GlGyRQ/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 5',
      'url'   => 'https://drive.google.com/file/d/1vlCuf98e0I23qWyjK-Xa893qADXtQAti/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5',
      'url'   => 'https://drive.google.com/file/d/12_--WfCn7_BJU6nd3mxKqfHS94NdiD0L/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 6',
      'url'   => 'https://drive.google.com/file/d/1TaywO9-A-P_B7OnOKbsrAISZ1NLdvbfi/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 6',
      'url'   => 'https://drive.google.com/file/d/1o7Ajn6NYX9yRZ_eI04nZNBojyGLJzOb1/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Tài liệu',
      'url'   => 'https://drive.google.com/file/d/11CWMttS2rNLv0JGZ5JAod9m3vOb4hEJM/preview'
    ],
    
  ],
  // 5. Lý thuyết cơ sở dữ liệu (27 mục)
  5 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/10NWOCpZdhiedMGk1zHXhZTPMFYTyo-Lh/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1',
      'url'   => 'https://drive.google.com/file/d/17xPDYa5gFxw0ZK5UEV-e_evt3b8lzQB-/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/1XcCRo1GNrW3v8QCO1jS_JPSCAwmiAg8o/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2.1',
      'url'   => 'https://drive.google.com/file/d/1HJWauaLmFG3Is9FcaIFSaBRQGD1mKAa2/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2.2',
      'url'   => 'https://drive.google.com/file/d/1vxy6wVdIXQwdK5X1SMmfBhEpWbs1dIip/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.1',
      'url'   => 'https://drive.google.com/file/d/1iEbz63dL48WrxD3PqKZjtpKV0QhrDl8o/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.2',
      'url'   => 'https://drive.google.com/file/d/1kpSljxjD7FAfRT673mCFn6JXJFlR5UBZ/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3.1',
      'url'   => 'https://drive.google.com/file/d/1FDEWXq0Oo_MyQXrJcVDQmfi8rQRJ6z_3/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.1',
      'url'   => 'https://drive.google.com/file/d/1UwsXBdbEq5emtxpj6-_Hw6ybCA0PSeIP/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/1CYOWS5p7Wa9p_D1d5F_-YOK_9rI2Rfp4/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/1B8JxLAMTAT3xDiht8wUYpmatSuTTD1im/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.1',
      'url'   => 'https://drive.google.com/file/d/1TrBlvwTxiiQE-flmWj87WEb8baoG1c_p/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.2',
      'url'   => 'https://drive.google.com/file/d/1sNFYcfFn0MkgvtzcDTpusn5AVpQz0a8V/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.3',
      'url'   => 'https://drive.google.com/file/d/1D0bLBQNDSjb6WurenCBWfWYf3PSFhPYC/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.4',
      'url'   => 'https://drive.google.com/file/d/13_L7dUvoxiPWuZKii33VrKyBCoynFtli/preview'
    ],
    
  ],
  // 6. Lập trình nhúng (20 mục)
  6 => [
    [
      'type'  => 'pdf',
      'title' => 'Chươngg 1',
      'url'   => 'https://drive.google.com/file/d/1SbsqmAWlxB6vSv-PqPJMtKOFzwdveHZP/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.1',
      'url'   => 'https://drive.google.com/file/d/1mcBl1HVCFsCEfN7KjmtWPVtviJy5wJ4g/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.2',
      'url'   => 'https://drive.google.com/file/d/1V_1UDC8qVP02AceapsMorSGUtCjaTV53/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 2.1',
      'url'   => 'https://drive.google.com/file/d/1Skg4LXcj9ZNlZyboYqO2Qn6RR9Wwps4G/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.2',
      'url'   => 'https://drive.google.com/file/d/1HXhcGLP7pNmwFco6_xEum99jUisPVyFv/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.3',
      'url'   => 'https://drive.google.com/file/d/1a9qGrchq17E31wV00pwIx6GdOdJxTRw9/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 3.1',
      'url'   => 'https://drive.google.com/file/d/1iz_MubmOov0Y8LlcGubnYSaOldVvGBKD/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/1FAveZvVfFWHFzmP7AQw5bnXCTnVLLE4X/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 4.1',
      'url'   => 'https://drive.google.com/file/d/1Ow8afotecvou_fxqkaJLgSLSK359PZgF/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 4.2',
      'url'   => 'https://drive.google.com/file/d/1li345UpjUAjsYLcwkaG5kcucxgAb1LdN/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 5',
      'url'   => 'https://drive.google.com/file/d/1q2WI7JAs-YDXxhshuRw0RDLp0sDPEWgw/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Tailieu',
      'url'   => 'https://drive.google.com/file/d/1YqgCa3b92XdaP04aoHKESK4THzbe1IRI/preview'
    ],
    
  ],
  // 7. Kinh tế vĩ mô (42 mục)
  7 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1-LXt-jonallf3I6sZ-B86dVyVJFY78mj/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1 ',
      'url'   => 'https://drive.google.com/file/d/1LKrDZMwYlVPAgUNowL_jNGRw4mwqZBSV/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Bài tập trắc nghiệm chương 1 ',
      'url'   => 'https://drive.google.com/file/d/1z1HqaxxReAOlf9IB8b6voupU4Mz5008K/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1t_KLcL5a69WSkCbAutWyjMbdIVoUSVcj/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.1',
      'url'   => 'https://drive.google.com/file/d/1enBksJPdfzzb-S2RMlVFddTlRPqnWv09/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.2',
      'url'   => 'https://drive.google.com/file/d/1ZyLCCP5ielPcrzmwdwBq-WkzcUS5Xzf8/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.3',
      'url'   => 'https://drive.google.com/file/d/1i6jkavRfEIpjeY5gtokDUshNjy0wmlHQ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.4',
      'url'   => 'https://drive.google.com/file/d/1MFbEzl9-l8WClYExjg3YX6D8Xd3D3fER/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3',
      'url'   => 'https://drive.google.com/file/d/1FzLbJa4c60IXSvL4CAYBgY3Whx04erCb/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.1',
      'url'   => 'https://drive.google.com/file/d/1x7XvgZ-2tZw5BqISnCnc3thcyuaRCCdo/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/1k1W2KhlYD1d6Zmt3QB4Yi-AibcBLP67E/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.3',
      'url'   => 'https://drive.google.com/file/d/1aX8M0A5Y5hA9oh_GW1NroxgljXQGjWaJ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.4',
      'url'   => 'https://drive.google.com/file/d/11a0k1gYv2RqhGDmxAEyY4lIX_rlnbGjb/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.5',
      'url'   => 'https://drive.google.com/file/d/1NEBJT4QAOtk0-OHASApKI1VzPaQJjX1F/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/14XcXJA885ZkEc1kh-eeGws1FBclbQgX9/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.1',
      'url'   => 'https://drive.google.com/file/d/1hQP-8AOR9fOLrQlgZE_BS_ytDzyVuL1O/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.2',
      'url'   => 'https://drive.google.com/file/d/1IuCAQ7lxcY_829qmAYtabpSeDC27EvZq/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.3',
      'url'   => 'https://drive.google.com/file/d/1z0_CSAQR4RcmQJFFNko_yTA5UtZXlabB/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 5',
      'url'   => 'https://drive.google.com/file/d/1oWyB_T7myf26lSLa6et_LznsKQaS8AWJ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 5',
      'url'   => 'https://drive.google.com/file/d/1QdfIMiHXvkrvhrU6LkBSf7yM5AP8jw2n/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 6',
      'url'   => 'https://drive.google.com/file/d/1f12WOQ5Qi8O9TQzo8MP_s-ryNWHuA4n0/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 7',
      'url'   => 'https://drive.google.com/file/d/14bsCPJINR0CumHUTIrx_qDLWQigelOAp/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 8',
      'url'   => 'https://drive.google.com/file/d/11fuJvFZoRjQJHEHwJ7d9i7mDCK08As_y/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 9',
      'url'   => 'https://drive.google.com/file/d/1SVuY8ZDIwKMSaDDMRPYOENsbQnx71oBr/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Trắc nghiệm ',
      'url'   => 'https://drive.google.com/file/d/1c7o499jOt0B_yB3M3Lmi9VIz49EnWcLq/preview'
    ],
    
  ],
  // 8. Hệ thống thiết bị di động (31 mục)
  8 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 0',
      'url'   => 'https://drive.google.com/file/d/1cJKZL5jH33Ql1R2u4WqbqzAiDx0TUpG_/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1astlkHRrpIMcIKcfXKY9Rd5-C4H_c_Il/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.1',
      'url'   => 'https://drive.google.com/file/d/1dacjfozeT55u8sE0lWQ43wxnw4D6iEcB/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.2',
      'url'   => 'https://drive.google.com/file/d/10X4lvmWERdpWili2qWcyGjCiY-6sEinE/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.3',
      'url'   => 'https://drive.google.com/file/d/1qqDOrJYlZ6mAJiTnQb8zSj2CLanF0edq/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2.1',
      'url'   => 'https://drive.google.com/file/d/1qEhPaytCV_1x3UKXHmSkOvJsrWoZtF0N/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2.2',
      'url'   => 'https://drive.google.com/file/d/1dH8ppU5Ip9FvxCj_xHJOOKEUKdeko8hz/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.1',
      'url'   => 'https://drive.google.com/file/d/1tlIpyGZ7Bsv91ZBkSFJwBWbm6_X_X4uo/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2.2',
      'url'   => 'https://drive.google.com/file/d/1IbYDvHqEfn63KuGuRRUn8CCCPup2f7Oc/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3.1',
      'url'   => 'https://drive.google.com/file/d/1xOUbXydqxGLLvCt2DKpS7a2p0ZhEFM9M/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3.2',
      'url'   => 'https://drive.google.com/file/d/1AN8_5-7Rk6_SDWQlBXFgCGTYcrx5ClOF/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.1',
      'url'   => 'https://drive.google.com/file/d/1JEjpGgKNMlo7C80_rXpS1IUAQk1UitrQ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.2',
      'url'   => 'https://drive.google.com/file/d/138dfTbC93S9Z4DxMXI8_VD300vyj9Oee/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.3',
      'url'   => 'https://drive.google.com/file/d/1Rx_Gs0bjqnQ8hZ0Z9AMBwmSJodHA8ngM/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.4',
      'url'   => 'https://drive.google.com/file/d/1niRG29SN1VZssHC7RU4PQX_b05kD2q8K/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3.5',
      'url'   => 'https://drive.google.com/file/d/1ex3Td1-U5EjPcJoqdK4Np2DrhYcJD1Dj/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/1Fesx4NMwptTFtcBG3d_4eTdkSCs2vxa8/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.1',
      'url'   => 'https://drive.google.com/file/d/1YPDXOSwV7IJOjnircDm4Spvvvb1jI7WB/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.2',
      'url'   => 'https://drive.google.com/file/d/1qP8aDJJugRfaQaJT3YA5nGwHYsFP8Lss/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4.3',
      'url'   => 'https://drive.google.com/file/d/1rrVYOfqdzHe6t6HFR_aQf9_nHQl8Ua69/preview'
    ],
    
  ],
  // 9. Cơ sở dữ liệu phân tán và hướng đối tượng (21 mục)
  9 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1Oa6w33pxo5Wj-mfxTZU_HsgXRaK6fOHw/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.1',
      'url'   => 'https://drive.google.com/file/d/15adfwy2ZhwUxRnSCwQuAbm_IB1Bi9BOL/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.2',
      'url'   => 'https://drive.google.com/file/d/1SCHeIhoy-w1HvOG3VDdGlagHDO9jS04M/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.3',
      'url'   => 'https://drive.google.com/file/d/1VCfhGApc1slCSGfRmg3AAPqwGvSr439l/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1_k0nqbuXhDuThitlEoXkuKTKelDtqQZl/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/1TW1hZo-nHACpVlA01VZWMr3LnBMGMTZf/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 3',
      'url'   => 'https://drive.google.com/file/d/1vNkwzfu892YJBJ3Y7yv0l8Cw-bzfjW-V/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 3',
      'url'   => 'https://drive.google.com/file/d/1M5Gq62pNHexI1KHuoy1YcjgfUBnchFps/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/19K_wPUFq75Pohb4DfF9Hj3m7ylaAfR4O/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4',
      'url'   => 'https://drive.google.com/file/d/1CyK0xJ-jxMNIQStf6HuAMigpYuGIp-U_/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Cơ sở nâng cao',
      'url'   => 'https://drive.google.com/file/d/1p6seWD7_5vHNTZhj_5Hx62NvH9SNAxgu/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video bài 5',
      'url'   => 'https://drive.google.com/file/d/1QzHJ5HCXzzntpIkR1gwG4RJ_nLm6GGt9/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video bài 6',
      'url'   => 'https://drive.google.com/file/d/1S2jfrYKhJGerSM1MCT8b_xS710O6vTs0/preview'
    ],
    
  ],
  // 10. Cảm biến và kỹ thuật đo lường (31 mục)
  10 => [
    [
      'type'  => 'pdf',
      'title' => 'Chương 1',
      'url'   => 'https://drive.google.com/file/d/1zzBwGHGtNKXpWqt_-q9pN7Ptu_raFMk8/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.1',
      'url'   => 'hhttps://drive.google.com/file/d/1AxZGrT44z2cP5ZyyWWzO7g9Y8u9Aau5y/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.2',
      'url'   => 'https://drive.google.com/file/d/1YfcaHA0iH4RO5Rwau9wmt5BK7BrW5Rc-/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 1.3',
      'url'   => 'https://drive.google.com/file/d/1IYUUJMesXsJOlAEii4XXK7rT8IiulXtv/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 2',
      'url'   => 'https://drive.google.com/file/d/1oJblBq9GMgNEpflHCLROfb51tLgRPHKe/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 2',
      'url'   => 'https://drive.google.com/file/d/1GXFV1G9nzqUW4rNZwjNZfBSYl4RZBvMV/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 3',
      'url'   => 'https://drive.google.com/file/d/1V-qca3oUaV0b0UnKBmNKQjPj9koJ9M7u/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 4',
      'url'   => 'https://drive.google.com/file/d/18wbumch_Qthsu1cu7cQ8_nBTWapd7EYa/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 4',
      'url'   => 'https://drive.google.com/file/d/1q88S9HLBikfyo0ljwNLNlGW5hpPgT-cZ/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video chương 5',
      'url'   => 'https://drive.google.com/file/d/1G6X3ntuxqys4SfoQmG6rdlyVG6i6pWcs/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 6',
      'url'   => 'https://drive.google.com/file/d/1EJPAFCjsMuuMYuFbA8nh4aHNAJbnqGLa/preview'
    ],
    [
      'type'  => 'video',
      'title' => 'Video 6',
      'url'   => 'https://drive.google.com/file/d/1SjGugpB65mg_XgbfRFs0VjQUQWb7S-88/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 7',
      'url'   => 'https://drive.google.com/file/d/11L8VMeMNzdRgmGng9CovOxKtZy5iicZW/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 8',
      'url'   => 'https://drive.google.com/file/d/1liDOSM_ii7CDt8dAlUAwu7-IMBhORKM3/preview'
    ],
    [
      'type'  => 'pdf',
      'title' => 'Chương 9',
      'url'   => 'https://drive.google.com/file/d/1mT8x8mEMNuWjebRRC_Bq24TOQdeQCIhu/preview'
    ],
    
  ]
];

$staticCoursesTitle = [
  1  => "Xây dựng hệ thống bảo vệ thông tin",
  2  => "Phân tích tài chính",
  3  => "Mô hình hóa phần mềm",
  4  => "Lý thuyết mật mã",
  5  => "Lý thuyết cơ sở dữ liệu",
  6  => "Lập trình nhúng",
  7  => "Kinh tế vĩ mô",
  8  => "Hệ thống thiết bị di động",
  9  => "Cơ sở dữ liệu phân tán và hướng đối tượng",
  10 => "Cảm biến và kỹ thuật đo lường"
];

if (!isset($staticCourseResources[$course_id])) {
  $resources   = [];
  $courseTitle = "Khóa học không tồn tại hoặc chưa có dữ liệu!";
} else {
  $resources   = $staticCourseResources[$course_id];
  $courseTitle = isset($staticCoursesTitle[$course_id]) ? $staticCoursesTitle[$course_id] : "Khóa học tĩnh";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($courseTitle); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; background: #fff; min-height: 100vh; }
    h1 { color: #ff7f50; margin-bottom: 10px; }
    .btn-back { display: inline-block; margin-bottom: 20px; padding: 8px 12px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
    .btn-back:hover { background: #0056b3; }
    .resource-list { margin-top: 30px; }
    .resource-item { background: #fdfdfd; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px; }
    .resource-item h3 { margin-bottom: 10px; color: #333; }
    iframe { width: 100%; max-width: 640px; height: 360px; border: none; margin-top: 10px; border-radius: 5px; }
    a.pdf-link { display: inline-block; margin-top: 10px; color: #007bff; text-decoration: none; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
    <a class="btn-back" href="student_dashboard.php">Quay về Dashboard</a>
    <h1><?php echo htmlspecialchars($courseTitle); ?></h1>
    <div class="resource-list">
      <?php if (empty($resources)): ?>
        <p>Chưa có video/PDF nào cho khóa học này.</p>
      <?php else: ?>
        <?php foreach ($resources as $res): ?>
          <div class="resource-item">
            <h3><?php echo htmlspecialchars($res['title']); ?></h3>
            <?php if ($res['type'] === 'video'): ?>
              <iframe src="<?php echo htmlspecialchars($res['url']); ?>" allow="autoplay; fullscreen" allowfullscreen></iframe>
            <?php elseif ($res['type'] === 'pdf'): ?>
              <iframe src="<?php echo htmlspecialchars($res['url']); ?>"></iframe>
              <a class="pdf-link" href="<?php echo htmlspecialchars($res['url']); ?>" target="_blank">Xem PDF toàn màn hình</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
