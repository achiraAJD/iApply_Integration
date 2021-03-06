USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddNote]    Script Date: 1/07/2022 11:57:34 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  Add note
| HISTORY:
| DATE			WHO    			DESCRIPTION OF CHANGE
| ---------------------------------------------------------------------------------
| 13/12/2016 	MXSAM			Creation
+--------------------------------------------------------------------------------------------
| SAMPLE USE: exec spWebAddNote 'FINAL LETTER RE PHOTO CAPTURE GENERATED', 'OA', 785006245, 0, 'APP', null, 'FUP', 'GEN', '2017-01-02', 'Current';
|								'FINAL LETTER RE PHOTO CAPTURE GENERATED', null, null, 0, 'LIC', 123456, 'CL', 'GEN', '2017-06-07', 'MXSAM';
+--------------------------------------------------------------------------------------------*/
ALTER PROCEDURE [dbo].[spWebAddNote]
		@Content VARCHAR(MAX),
		@Content_Rich VARCHAR(MAX) = null,
		@Content_Abbreviated VARCHAR(50) = null,
		@Switch VARCHAR(16) = null,
		@Param VARCHAR(16) = null,
		@NT_IsSensitive BIT,
		@OBJT_Code VARCHAR(16),
		@NT_OBJ_ID INT,
		@NTS_Code VARCHAR(16),
		@NTYP_Code VARCHAR(16),
		@NT_FollowUpDate DATETIME = null,
		@AU_Name VARCHAR(16) = 'SYSTM',
		@AU_Name_Allocated VARCHAR(16) = null,
		@Attachment varchar(max) = null,
		@Notification char(1) = null,
		@Output bit = null
		
AS

SET NOCOUNT ON

	DECLARE @NT_OBJT_ID NUMERIC(18), @NT_NTS_ID NUMERIC(18), @NT_NTYP_ID NUMERIC(18), @NT_AU_ID NUMERIC (18), @NT_AU_ID_Allocated NUMERIC(18) = NULL, @AU_FullName varchar(64) = NULL, @AU_Email_Allocated varchar(128) = NULL
	SELECT @NT_OBJT_ID = OBJT_ID FROM ObjectType WHERE OBJT_Code = @OBJT_Code;
	SELECT @NT_NTS_ID = NTS_ID FROM NoteStatus WHERE NTS_Code = @NTS_Code;
	SELECT @NT_NTYP_ID = NTYP_ID FROM NoteType WHERE NTYP_Code = @NTYP_Code;
	SELECT @NT_AU_ID = AU_ID, @AU_FullName = AU_FullName FROM AppUsers WHERE AU_Name = @AU_Name;
    if @AU_Name_Allocated is not NULL SELECT @NT_AU_ID_Allocated = AU_ID, @AU_Email_Allocated = AU_Email FROM AppUsers WHERE AU_Name = @AU_Name_Allocated;


	IF @Switch = 'OA'
		SELECT @NT_OBJ_ID = APP_ID from Applications INNER JOIN OnlineApplication on APP_OA_ID = OA_ID WHERE OA_ReferenceNumber = @Param AND OA_OAS_ID = 2;
	
	IF @AU_Name_Allocated = 'Current'
		SELECT TOP 1 @NT_AU_ID_Allocated = FA_AU_ID_AllocTo FROM FileAllocation WHERE FA_OBJT_ID = @NT_OBJT_ID AND FA_OBJ_ID = @NT_OBJ_ID ORDER BY FA_AllocatedDate DESC;

	IF @Content_Rich IS NULL
		SET @Content_Rich = @Content

	IF @Content_Abbreviated IS NULL
		SET @Content_Abbreviated = LEFT(@Content,50)
	
	INSERT INTO Note (NT_FullNote,NT_RichText,NT_Abbreviated,NT_IsSensitive,NT_NoteDate,NT_OBJT_ID,NT_OBJ_ID,NT_NTS_ID,NT_NTYP_ID,NT_AU_ID,CreationUser,LastUpdateUser,NT_FollowUpDate,NT_AU_ID_Allocated)
	VALUES (
		'<HTML><BODY STYLE="text-align:Left;font-family:Microsoft Sans Serif;font-style:normal;font-weight:normal;font-size:11pt;color:#000000;"><P><SPAN>'+@Content+'</SPAN></P></BODY></HTML>',
		'<Section xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation" xml:space="preserve" TextAlignment="Left" LineHeight="Auto" IsHyphenationEnabled="False" xml:lang="en-us" FlowDirection="LeftToRight" NumberSubstitution.CultureSource="User" NumberSubstitution.Substitution="AsCulture" FontFamily="Microsoft Sans Serif" FontStyle="Normal" FontWeight="Normal" FontStretch="Normal" FontSize="11" Foreground="#FF000000" Typography.StandardLigatures="True" Typography.ContextualLigatures="True" Typography.DiscretionaryLigatures="False" Typography.HistoricalLigatures="False" Typography.AnnotationAlternates="0" Typography.ContextualAlternates="True" Typography.HistoricalForms="False" Typography.Kerning="True" Typography.CapitalSpacing="False" Typography.CaseSensitiveForms="False" Typography.StylisticSet1="False" Typography.StylisticSet2="False" Typography.StylisticSet3="False" Typography.StylisticSet4="False" Typography.StylisticSet5="False" Typography.StylisticSet6="False" Typography.StylisticSet7="False" Typography.StylisticSet8="False" Typography.StylisticSet9="False" Typography.StylisticSet10="False" Typography.StylisticSet11="False" Typography.StylisticSet12="False" Typography.StylisticSet13="False" Typography.StylisticSet14="False" Typography.StylisticSet15="False" Typography.StylisticSet16="False" Typography.StylisticSet17="False" Typography.StylisticSet18="False" Typography.StylisticSet19="False" Typography.StylisticSet20="False" Typography.Fraction="Normal" Typography.SlashedZero="False" Typography.MathematicalGreek="False" Typography.EastAsianExpertForms="False" Typography.Variants="Normal" Typography.Capitals="Normal" Typography.NumeralStyle="Normal" Typography.NumeralAlignment="Normal" Typography.EastAsianWidths="Normal" Typography.EastAsianLanguage="Normal" Typography.StandardSwashes="0" Typography.ContextualSwashes="0" Typography.StylisticAlternates="0"><Paragraph><Run xml:lang="en-au">'+@Content_Rich+'</Run></Paragraph></Section>',
		@Content_Abbreviated,
		0,
		GETDATE(),
		@NT_OBJT_ID,
		@NT_OBJ_ID,
		@NT_NTS_ID,
		@NT_NTYP_ID,
		@NT_AU_ID,
		'SYSTM',
		'SYSTM',
		@NT_FollowUpDate,
		@NT_AU_ID_Allocated
	)

	IF @Output IS NOT NULL
		SELECT @@IDENTITY AS NT_ID
	
	IF @Attachment IS NOT NULL BEGIN
		INSERT INTO Documents (DOC_DT_ID, DOC_File, DOC_FileName, CreationDateTime, Creationuser, LastUpdateDateTime, LastUpdateUser, DOC_OBJ_ID, DOC_OBJT_ID)
		VALUES (
			(select DT_ID from DocumentTypes where DT_Code = 'NTAT'),
			cast (cast(@Attachment as varchar(max)) as varbinary(max)),
			'NoteAttachment.html',
			GETDATE(),
			'SYSTM',
			GETDATE(),
			'SYSTM',
			@@IDENTITY,
			(select OBJT_ID from ObjectType where OBJT_Code = 'NT')
		)
	END

	IF @Notification = 'Y' AND @NT_AU_ID_Allocated != @NT_AU_ID AND @AU_Email_Allocated IS NOT NULL BEGIN
		DECLARE @Reference VARCHAR(32) = CASE
			WHEN @OBJT_Code in ('LAM', 'APP') THEN (select 'App No ' + CAST(APP_ApplicNumber AS VARCHAR(16)) from Applications where APP_ID = @NT_OBJ_ID)
			ELSE ''
		END
		DECLARE @Subject varchar(256) = @AU_FullName + ' has left you a note (' + @Reference + ')'
		exec [dbo].spWebSendNotification @Type = 'FreeEmail', @ToAddress = @AU_Email_Allocated, @Subject = @Subject, @Content = @Content
	END

SET ROWCOUNT 0