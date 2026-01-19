const express = require('express');
const ReportService = require('../services/reportService');
const PDFService = require('../services/pdfService');
const { protect } = require('../middleware/auth');

const router = express.Router();

router.use(protect);

// @route   GET /api/reports/mumayyiz-talaba
// @desc    Get list of Mumayyiz Talaba (boys with zero absences)
router.get('/mumayyiz-talaba', async (req, res) => {
  try {
    const data = await ReportService.getMumayyizTalaba();
    res.status(200).json({ success: true, data });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/mumtaz-talaba
// @desc    Get list of Mumtaz Talaba (boys with marks >= 85)
router.get('/mumtaz-talaba', async (req, res) => {
  try {
    const data = await ReportService.getMumtazTalaba();
    res.status(200).json({ success: true, data });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/mumayyiz-talibaat
// @desc    Get list of Mumayyiz Talibaat (girls with zero absences)
router.get('/mumayyiz-talibaat', async (req, res) => {
  try {
    const data = await ReportService.getMumayyizTalibaat();
    res.status(200).json({ success: true, data });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/mumtaz-talibaat
// @desc    Get list of Mumtaz Talibaat (girls with marks >= 85)
router.get('/mumtaz-talibaat', async (req, res) => {
  try {
    const data = await ReportService.getMumtazTalibaat();
    res.status(200).json({ success: true, data });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/halqa-performance/:halqaId
// @desc    Get halqa performance report
router.get('/halqa-performance/:halqaId', async (req, res) => {
  try {
    const data = await ReportService.getHalqaPerformanceReport(req.params.halqaId);
    res.status(200).json({ success: true, data });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/kashf/:examId/download
// @desc    Download Kashf Imtehaan PDF
router.get('/kashf/:examId/download', async (req, res) => {
  try {
    const pdfResult = await PDFService.generateKashfImtehaan(req.params.examId);
    res.download(pdfResult.filePath, pdfResult.fileName);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/reports/student-result/:studentId/:examId/download
// @desc    Download individual student result PDF
router.get('/student-result/:studentId/:examId/download', async (req, res) => {
  try {
    const pdfResult = await PDFService.generateIndividualResultPDF(
      req.params.studentId,
      req.params.examId
    );
    res.download(pdfResult.filePath, pdfResult.fileName);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

module.exports = router;