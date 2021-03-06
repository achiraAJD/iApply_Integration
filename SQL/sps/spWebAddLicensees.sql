USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddLicensees]    Script Date: 1/07/2022 11:49:13 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Achira Warnakulasuriya>
-- Create date: <21/10/2021>
-- Description:	<Update records in Licensees and LicenceEntities  tables in LOGIC>
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddLicensees] 
	@LEE_LIC_ID			NUMERIC(18,0),
	@LEE_AS_ID			NUMERIC(18,0),
	@LEE_Name1			VARCHAR(40),
	@LEE_Name2			VARCHAR(40) = NULL,
	@LEE_DateFrom		DATETIME = NULL,
	@LEE_DateTo			DATETIME = NULL,
	@LEE_Status			VARCHAR(1) = NULL,
	@LEE_Notes			VARCHAR(255) = NULL,
	@LE_ENT_ID			VARCHAR(MAX),
	@LastUpdateUser		VARCHAR(50)	
		
AS
BEGIN
	SET NOCOUNT ON;

	DECLARE @lee_id	NUMERIC(18,0),@le_id NUMERIC (18,0)
		
	INSERT INTO Licensees
	(LEE_LIC_ID,LEE_AS_ID,LEE_Name1,LEE_Name2,LEE_DateFrom,LEE_DateTo,LEE_Status,LEE_Notes,LastUpdateUser,LastUpdateDateTime,CreationDateTime,CreationUser)
	VALUES
	(@LEE_LIC_ID, @LEE_AS_ID, @LEE_Name1, @LEE_Name2, @LEE_DateFrom, @LEE_DateTo, @LEE_Status, @LEE_Notes, @LastUpdateUser, GETDATE(), GETDATE(), @LastUpdateUser)
		
	SELECT @lee_id = SCOPE_IDENTITY() --getting LEE_ID
	
	INSERT INTO LicenceEntities (LE_ENT_ID,LE_LEE_ID,CreationDateTime,CreationUser,LastUpdateDateTime,LastUpdateUser)
	SELECT CAST(value AS INT), @lee_id, GETDATE(), @LastUpdateUser, GETDATE(), @LastUpdateUser FROM OPENJSON(@LE_ENT_ID)
	
	SELECT @lee_id as LEE_ID, @LEE_Name1 as LEE_Name1
END

