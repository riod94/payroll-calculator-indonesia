<?php

declare(strict_types=1);

namespace PayrollCalculator\Types;

enum TaxStatus: int
{
    case PERMANENT_EMPLOYEE = 1; // Pegawai Tetap
    case NON_PERMANENT_EMPLOYEE = 2; // Pegawai Tidak Tetap
    case NOT_EMPLOYEE_WHO_ARE_SUSTAINABLE = 3; // Bukan Pegawai berkesinambungan
    case NOT_EMPLOYEE_WHO_ARE_SUSTAINABLE_AND_HAVE_OTHER_INCOME = 4; // Bukan Pegawai berkesinambungan dan Memiliki penghasilan lainnya
    case NOT_EMPLOTEE_WHO_ARE_UNSUSTAINABLE = 5; // Bukan Pegawai tidak berkesinambungan
    case OTHER_SUBJECTS = 6; // Subject pajak lainnya seperti: Peserta Kegiatan, Pensiunan dan Bukan Pegawai
    case BOARD_OF_COMMISSIONERS = 7; // Dewan Pengawas / Komisaris
    case FOREIGN_INDIVIDUAL_TAXPAYER = 8; // Wajib Pajak Asing
}
