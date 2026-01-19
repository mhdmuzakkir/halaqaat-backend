const express = require('express');
const Exam = require('../models/Exam');
const ExamMarks = require('../models/ExamMarks');
const Student = require('../models/Student');
const Halqa = require('../models/Halqa');
const AuditLog = require('../models/AuditLog');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

// Middleware to verify mumtaheen
router.use(protect);
router.use(authorize('mumtaheen'));

// @route   GET /api/mumtaheen/exams
// @desc    Get exams assigned to this mumtaheen
router.get('/exams', async (req, res) => {
  try {
    const exams = await Exam.find({ mumtaheenId: req.user.id })
      .populate('halqaId', 'name gender')
      .populate('mumtaheenId', 'name');

    res.status(200).json({ success: true, data: exams });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/mumtaheen/exam/:examId/details
// @desc    Get exam details and students for marks entry
router.get('/exam/:examId/details', async (req, res) => {
  try {
    const exam = await Exam.findById(req.params.examId)
      .populate('halqaId', 'name gender')
      .populate('mumtaheenId', 'name');

    const examMarks = await ExamMarks.find({ examId: req.params.examId })
      .populate('studentId', 'name fatherName gender shuba');

    res.status(200).json({ 
      success: true, 
      exam,
      examMarks
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/mumtaheen/marks/enter
// @desc    Enter marks for student after exam
router.post('/marks/enter', async (req, res) => {
  try {
    const { examMarksId, subjectMarks, husnSawt, tajweed, izaafaat, sulookMarks, extraMarks } = req.body;

    const examMark = await ExamMarks.findByIdAndUpdate(
      examMarksId,
      {
        subjectMarks,
        husnSawt,
        tajweed,
        izaafaat,
        sulookMarks,
        extraMarks,
        marksEnteredAt: new Date(),
        enteredBy: req.user.id
      },
      { new: true }
    );

    await AuditLog.create({
      userId: req.user.id,
      action: 'Entered exam marks',
      examId: examMark.examId,
      studentId: examMark.studentId,
      details: { 
        totalMarks: examMark.totalMarks,
        grade: examMark.grade
      }
    });

    res.status(200).json({ success: true, data: examMark });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   PUT /api/mumtaheen/exam/:examId/complete
// @desc    Mark exam as completed
router.put('/exam/:examId/complete', async (req, res) => {
  try {
    const exam = await Exam.findByIdAndUpdate(
      req.params.examId,
      { 
        examStatus: 'completed',
        kashfGeneratedAt: new Date()
      },
      { new: true }
    );

    res.status(200).json({ success: true, data: exam });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

module.exports = router;