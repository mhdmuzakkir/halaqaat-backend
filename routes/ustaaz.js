const express = require('express');
const User = require('../models/User');
const Halqa = require('../models/Halqa');
const Student = require('../models/Student');
const Exam = require('../models/Exam');
const ExamMarks = require('../models/ExamMarks');
const AuditLog = require('../models/AuditLog');
const { protect, authorize } = require('../middleware/auth');

const router = express.Router();

// Middleware to verify ustaaz
router.use(protect);
router.use(authorize('ustaaz'));

// @route   GET /api/ustaaz/halqas
// @desc    Get halqas assigned to this ustaaz
router.get('/halqas', async (req, res) => {
  try {
    const halqas = await Halqa.find({ ustaazId: req.user.id });

    res.status(200).json({ success: true, data: halqas });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/ustaaz/halqa/:halqaId/students
// @desc    Get students in specific halqa
router.get('/halqa/:halqaId/students', async (req, res) => {
  try {
    const students = await Student.find({ 
      halqaId: req.params.halqaId,
      status: 'active'
    });

    res.status(200).json({ success: true, data: students });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   GET /api/ustaaz/exam/pending
// @desc    Get pending exams for this ustaaz's halqas
router.get('/exam/pending', async (req, res) => {
  try {
    const halqas = await Halqa.find({ ustaazId: req.user.id });
    const halqaIds = halqas.map(h => h._id);

    const exams = await Exam.find({ 
      halqaId: { $in: halqaIds },
      examStatus: 'pending'
    }).populate('halqaId', 'name');

    res.status(200).json({ success: true, data: exams });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

// @route   POST /api/ustaaz/exam/:examId/submit-details
// @desc    Submit exam details for each student in halqa
router.post('/exam/:examId/submit-details', async (req, res) => {
  try {
    const { examId } = req.params;
    const { marksData } = req.body; // Array of student marks objects

    const exam = await Exam.findById(examId);
    if (!exam) {
      return res.status(404).json({ message: 'Exam not found' });
    }

    const savedMarks = [];

    for (let markData of marksData) {
      let examMark = await ExamMarks.findOne({
        examId,
        studentId: markData.studentId
      });

      if (!examMark) {
        examMark = new ExamMarks({
          examId,
          studentId: markData.studentId,
          halqaId: exam.halqaId,
          shuba: markData.shuba
        });
      }

      examMark.fromPage = markData.fromPage;
      examMark.toPage = markData.toPage;
      examMark.sulookNumber = markData.sulookNumber;
      examMark.mumayyizStatus = markData.mumayyizStatus;
      examMark.ustaazSubmittedAt = new Date();

      await examMark.save();
      savedMarks.push(examMark);
    }

    exam.examStatus = 'ongoing';
    await exam.save();

    await AuditLog.create({
      userId: req.user.id,
      action: 'Submitted exam details',
      examId,
      details: { studentCount: savedMarks.length }
    });

    res.status(200).json({ success: true, data: savedMarks });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
});

module.exports = router;