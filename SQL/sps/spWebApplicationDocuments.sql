USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebApplicationDocuments]    Script Date: 1/07/2022 11:52:06 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		MXSAM
-- Create date: 03/05/2019
-- Description:	...
-- =============================================
--Grant execute on [spWebApplicationDocuments] to WebUser
ALTER PROCEDURE [dbo].[spWebApplicationDocuments]
	@Switch varchar(32),
	@APP_ID numeric(18,0) = null,
	@APP_IDs varchar(256) = null,
	@AT_Prefix varchar(8) = null,
	@AT_ID numeric(18,0) = null,
	@DT_ID numeric(18,0) = null,
	@DT_Code varchar(16) = null,
	@DOC_ID numeric(18,0) = null,
	@GUID varchar(36) = null,
	@DOC_AOR_ID numeric(18,0) = null,
	@CB_Code varchar(8) = null,
	@AU_Name varchar(16) = null,
	@DOC_File varchar(max) = null,
	@DOC_FileStream varchar(max) = null,
	@DOC_Filename varchar(200) = null,
	@DOC_Author varchar(200) = null,
	@DOC_Title varchar(200) = null,
	@DOC_Subject varchar(200) = null,
	@DOC_ValidFromDate datetime = null,
	@DOC_ValidToDate datetime = null,
	@DOC_LIC_ID numeric(18,0) = null,
	@OBJ_ID numeric(18,0) = null,
	@OBJT_Code varchar(16) = null,
	@DOC_Notes varchar(256) = null,
	@AP_ID numeric(18,0) = null,
	@ENT_ID numeric(18,0) = null,
	@LIC_ID numeric(18,0) = null,
	@AS_ID numeric(18,0) = null

AS
BEGIN
	SET NOCOUNT ON;

	DECLARE @CB_ID numeric(18, 0)
	
	IF @Switch = 'ApplicationDocuments' BEGIN
		select LicenceSystemApplicationDocumentInfo.*, CB_Code, CB_Description, DOC_Title, GUID, DOC_Notes
		from LicenceSystemApplicationDocumentInfo
		inner join ComboBoxes on LSADI_CB_ID_Status = CB_ID
		left join Documents on LSADI_DOC_ID = DOC_Id
		where LSADI_APP_ID in (select value from OPENJSON(@APP_IDs))
		order by LSADI_DT_ID asc
	END

	IF @Switch = 'UpdateApplicationDocumentInfo' BEGIN
		SELECT @CB_ID = CB_ID FROM ComboBoxes INNER JOIN ComboBoxNames ON CB_CBN_ID = CBN_ID WHERE CBN_Name = 'Licence System Document Status' AND CB_Code = @CB_Code
		IF @DOC_ID IS NULL BEGIN
			IF @CB_Code = 'REQ'
				DELETE FROM LicenceSystemApplicationDocumentInfo WHERE LSADI_APP_ID = @APP_ID AND LSADI_AT_ID = @AT_ID AND LSADI_DT_ID = @DT_ID AND LSADI_DOC_ID IS NULL
			IF @CB_Code = 'WVD'
				INSERT INTO LicenceSystemApplicationDocumentInfo (LSADI_APP_ID, LSADI_AT_ID, LSADI_DT_ID, LSADI_CB_ID_Status, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser) VALUES (@APP_ID, @AT_ID, @DT_ID, @CB_ID, GETDATE(), @AU_Name, GETDATE(), @AU_Name)
		END
		ELSE BEGIN
			UPDATE LicenceSystemApplicationDocumentInfo SET LSADI_CB_ID_Status = @CB_ID, LastUpdateDateTime = GETDATE(), LastUpdateUser = @AU_Name WHERE LSADI_APP_ID = @APP_ID AND LSADI_AT_ID = @AT_ID AND LSADI_DT_ID = @DT_ID AND LSADI_DOC_ID = @DOC_ID
		END
		SELECT GETDATE() AS LastUpdateDateTime
	END

	IF @Switch = 'UploadDocument' BEGIN
		declare
			@DOC_File_decoded varbinary(max) = null,
			@DOC_FileStream_decoded varbinary(max) = null
		IF @DOC_File IS NOT NULL
			set @DOC_File_decoded = cast('' as xml).value('xs:base64Binary(sql:variable("@DOC_File"))', 'varbinary(max)')
		ELSE
			set @DOC_FileStream_decoded = cast('' as xml).value('xs:base64Binary(sql:variable("@DOC_FileStream"))', 'varbinary(max)')
		
		-- add new document
		IF @DOC_ID IS NULL BEGIN
			DECLARE @DOC_OBJT_ID NUMERIC(18,0)
			SET @DOC_OBJT_ID = (SELECT TOP 1 OBJT_ID FROM ObjectType WHERE OBJT_Code = @OBJT_Code)
			IF @DT_ID IS NULL SELECT @DT_ID = DT_ID FROM DocumentTypes WHERE DT_Code = @DT_Code AND DT_OBJT_ID = @DOC_OBJT_ID
			IF @DT_ID IS NULL SELECT @DT_ID = DT_ID FROM DocumentTypes WHERE DT_Code = @DT_Code

			INSERT INTO Documents (DOC_LIC_ID, DOC_DT_ID, DOC_Filename, DOC_Author, DOC_Title, DOC_Subject, LastUpdateDateTime, LastUpdateUser, CreationDateTime, CreationUser, DOC_OBJ_ID, DOC_OBJT_ID, DOC_File, DOC_FileStream, DOC_ValidFromDate, DOC_UploadSuccessful, DOC_Notes)
			VALUES(@DOC_LIC_ID, @DT_ID, @DOC_Filename, @DOC_Author, @DOC_Title, @DOC_Subject, GETDATE(), @AU_Name, GETDATE(), @AU_Name, @OBJ_ID, @DOC_OBJT_ID, @DOC_File_decoded, @DOC_FileStream_decoded, GETDATE(), 1, @DOC_Notes)

			SELECT @DOC_ID = SCOPE_IDENTITY()

			-- update LicenceSystemApplicationDocumentInfo table if required
			IF @OBJT_Code = 'APP' OR @OBJT_Code = 'LAM' BEGIN
				SET @CB_ID = (SELECT TOP 1 CB_ID FROM ComboBoxes INNER JOIN ComboBoxNames ON CB_CBN_ID = CBN_ID WHERE CBN_Name = 'Licence System Document Status' AND CB_Code = 'REC')
				IF @AT_ID IS NULL SELECT @AT_ID = AT_ID FROM ApplicationTypes WHERE AT_Prefix = @AT_Prefix
				INSERT INTO LicenceSystemApplicationDocumentInfo (LSADI_AT_ID, LSADI_DT_ID, LSADI_CB_ID_Status, LSADI_APP_ID, LSADI_DOC_ID, LSADI_AP_ID, LSADI_ENT_ID, CreationDateTime, CreationUser, LastUpdateDateTime, LastUpdateUser)
				VALUES (@AT_ID, @DT_ID, @CB_ID, @OBJ_ID, @DOC_ID, @AP_ID, @ENT_ID, GETDATE(), @AU_Name, GETDATE(), @AU_Name)
			END
		END
		-- update existing document
		ELSE
			UPDATE Documents SET DOC_File = @DOC_File_decoded, DOC_FileStream = @DOC_FileStream_decoded, DOC_Filename = @DOC_Filename, DOC_Title = @DOC_Title, LastUpdateDateTime = GETDATE(), LastUpdateUser = @AU_Name WHERE DOC_ID = @DOC_ID

		SELECT @DOC_ID AS DOC_ID, GUID from Documents WHERE DOC_ID = @DOC_ID
	END

	IF @Switch = 'FetchDocument' BEGIN
		IF @DOC_ID IS NOT NULL AND @GUID IS NOT NULL
			SELECT * from Documents WHERE DOC_ID = @DOC_ID and GUID = @GUID
		ELSE IF @DOC_AOR_ID IS NOT NULL
			SELECT * from Documents WHERE DOC_AOR_ID = @DOC_AOR_ID
		ELSE IF @LIC_ID IS NOT NULL AND @AS_ID IS NOT NULL
			SELECT * from Documents WHERE DOC_LIC_ID = @LIC_ID AND DOC_DT_ID = CASE WHEN @AS_ID = 2 THEN 7 ELSE 1 END
	END
	
	IF @Switch = 'DeleteDocument' BEGIN
		IF @DOC_ID IS NOT NULL AND @GUID IS NOT NULL
			DELETE FROM Documents WHERE DOC_ID = @DOC_ID and GUID = @GUID
		SELECT @@ROWCOUNT rowsDeleted
	END
	
	IF @Switch = 'CloneDocument' BEGIN
		INSERT INTO Documents (DOC_LIC_ID, DOC_DT_ID, DOC_Filename, DOC_Author, DOC_Title, DOC_Subject, LastUpdateDateTime, LastUpdateUser, CreationDateTime, CreationUser, DOC_OBJ_ID, DOC_OBJT_ID, DOC_FileStream, DOC_ValidFromDate, DOC_UploadSuccessful, DOC_Notes)
		SELECT DOC_LIC_ID, DOC_DT_ID, DOC_Filename, DOC_Author, DOC_Title, DOC_Subject, GETDATE(), @AU_Name, GETDATE(), @AU_Name, DOC_OBJ_ID, DOC_OBJT_ID, DOC_FileStream, DOC_ValidFromDate, DOC_UploadSuccessful, DOC_Notes from Documents WHERE DOC_ID = @DOC_ID
		SELECT DOC_ID, GUID FROM Documents WHERE DOC_ID = SCOPE_IDENTITY()
	END
	
	IF @Switch = 'ArchivePlan' BEGIN
		SELECT @DT_ID = DT_ID FROM DocumentTypes WHERE DT_Code = 'PLAN_OLD'
		UPDATE Documents SET DOC_DT_ID = @DT_ID, LastUpdateDateTime = GETDATE(), LastUpdateUser = @AU_Name WHERE DOC_LIC_ID = @DOC_LIC_ID AND DOC_Title = @DOC_Title AND DOC_DT_ID = (SELECT DT_ID FROM DocumentTypes WHERE DT_Code = 'PLAN_C')
	END

	IF @Switch = 'LoadPlans' BEGIN
		SELECT
			DOC_ID, DOC_FileName, DT_Code, DOC_Subject, DOC_ValidFromDate, DOC_ValidToDate, GUID FROM
		Documents
		INNER JOIN DocumentTypes ON DT_ID = DOC_DT_ID
		WHERE DOC_LIC_ID = @LIC_ID AND DT_Code in ('PLAN_C', 'PLAN_OLD')
		ORDER BY DOC_Subject, DT_Code, DOC_FileName
	END
	
	IF @Switch = 'UpdateDocument' BEGIN
		IF @DT_Code IS NOT NULL
			UPDATE Documents SET DOC_DT_ID = (select DT_ID from DocumentTypes where DT_Code = @DT_Code and DT_IsOccupational = 0) WHERE DOC_ID = @DOC_ID
		IF @DOC_ValidFromDate IS NOT NULL
			UPDATE Documents SET DOC_ValidFromDate = @DOC_ValidFromDate WHERE DOC_ID = @DOC_ID
		IF @DOC_ValidToDate IS NOT NULL
			UPDATE Documents SET DOC_ValidToDate = @DOC_ValidToDate WHERE DOC_ID = @DOC_ID
		SELECT @DOC_ID as DOC_ID
	END
END
